<?php

namespace Backend\Utils;

use Backend\Core\App;

class InviteManager
{
    private static $db = null;

    private static function db() {
        if (!self::$db) {
            self::$db = App::resolve('Core\Database');
        }
        return self::$db;
    }

    // Check if user is superadmin
    public static function isSuperAdmin($userId) {
        $db = self::db();
        $result = $db->query(
            "SELECT accessLevel FROM users WHERE id = :id",
            [':id' => $userId]
        );
        return !empty($result) && $result[0]['accessLevel'] >= 5; // Level 5+ = superadmin
    }

    // ========================================
    // VIEW INVITES
    // ========================================

    public static function getInviteById($inviteId) {
        $db = self::db();
        $result = $db->query(
            "SELECT i.*, u.username as generatorName, u2.username as usedByName
             FROM inviteCodes i
             LEFT JOIN users u ON i.generatorId = u.id
             LEFT JOIN users u2 ON i.usedBy = u2.id
             WHERE i.id = :id",
            [':id' => $inviteId]
        );
        return !empty($result) ? $result[0] : null;
    }

    public static function getAllInvites($limit = 50, $offset = 0, $filters = []) {
        $db = self::db();

        $query = "SELECT i.*, u.username as generatorName, u2.username as usedByName,
                         COUNT(CASE WHEN i.used = 1 THEN 1 END) OVER() as totalUsed
                  FROM inviteCodes i
                  LEFT JOIN users u ON i.generatorId = u.id
                  LEFT JOIN users u2 ON i.usedBy = u2.id
                  WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'unused') {
                $query .= " AND i.used = 0 AND i.isRevoked = 0 AND (i.expiresAt IS NULL OR i.expiresAt > NOW())";
            } elseif ($filters['status'] === 'used') {
                $query .= " AND i.used = 1";
            } elseif ($filters['status'] === 'revoked') {
                $query .= " AND i.isRevoked = 1";
            } elseif ($filters['status'] === 'expired') {
                $query .= " AND i.expiresAt IS NOT NULL AND i.expiresAt < NOW()";
            }
        }

        if (!empty($filters['generatorId'])) {
            $query .= " AND i.generatorId = :generatorId";
            $params[':generatorId'] = $filters['generatorId'];
        }

        if (!empty($filters['batchId'])) {
            $query .= " AND i.batchId = :batchId";
            $params[':batchId'] = $filters['batchId'];
        }

        $query .= " ORDER BY i.createdAt DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        return $db->query($query, $params);
    }

    public static function getInviteStats($generatorId = null) {
        $db = self::db();

        $query = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN used = 0 AND isRevoked = 0 AND (expiresAt IS NULL OR expiresAt > NOW()) THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN used = 1 THEN 1 ELSE 0 END) as used,
                    SUM(CASE WHEN isRevoked = 1 THEN 1 ELSE 0 END) as revoked,
                    SUM(CASE WHEN expiresAt IS NOT NULL AND expiresAt < NOW() THEN 1 ELSE 0 END) as expired
                  FROM inviteCodes
                  WHERE 1=1";
        $params = [];

        if ($generatorId) {
            $query .= " AND generatorId = :generatorId";
            $params[':generatorId'] = $generatorId;
        }

        $result = $db->query($query, $params);
        return !empty($result) ? $result[0] : null;
    }

    // ========================================
    // CREATE INVITES
    // ========================================

    public static function createInvite($generatorId, $expirationDays = 7, $maxUses = 1) {
        $db = self::db();
        $code = self::generateUniqueCode();
        $inviteId = \bin2hex(\random_bytes(18));

        $expiresAt = $expirationDays ? date('Y-m-d H:i:s', strtotime("+{$expirationDays} days")) : null;

        $db->query(
            "INSERT INTO inviteCodes (id, code, generatorId, expiresAt, maxUses)
             VALUES (:id, :code, :generatorId, :expiresAt, :maxUses)",
            [
                ':id' => $inviteId,
                ':code' => $code,
                ':generatorId' => $generatorId,
                ':expiresAt' => $expiresAt,
                ':maxUses' => $maxUses
            ]
        );

        AdvancedFeatures::logAudit($generatorId, null, 'create_invite', 'invite', $inviteId,
            ['code' => $code, 'expiresAt' => $expiresAt, 'maxUses' => $maxUses]);

        return [
            'id' => $inviteId,
            'code' => $code,
            'expiresAt' => $expiresAt,
            'maxUses' => $maxUses
        ];
    }

    public static function createBulkInvites($generatorId, $count, $expirationDays = 7, $batchName = '') {
        $db = self::db();
        $batchId = \bin2hex(\random_bytes(18));
        $codes = [];

        // Create batch record
        $db->query(
            "INSERT INTO invite_batches (id, generatorId, batchName, totalCodes, expiresAt)
             VALUES (:id, :generatorId, :batchName, :totalCodes, :expiresAt)",
            [
                ':id' => $batchId,
                ':generatorId' => $generatorId,
                ':batchName' => $batchName ?: "Batch " . date('Y-m-d H:i'),
                ':totalCodes' => $count,
                ':expiresAt' => $expirationDays ? date('Y-m-d H:i:s', strtotime("+{$expirationDays} days")) : null
            ]
        );

        // Create individual codes
        for ($i = 0; $i < $count; $i++) {
            $code = self::generateUniqueCode();
            $inviteId = \bin2hex(\random_bytes(18));
            $expiresAt = $expirationDays ? date('Y-m-d H:i:s', strtotime("+{$expirationDays} days")) : null;

            $db->query(
                "INSERT INTO inviteCodes (id, code, generatorId, batchId, expiresAt, maxUses)
                 VALUES (:id, :code, :generatorId, :batchId, :expiresAt, 1)",
                [
                    ':id' => $inviteId,
                    ':code' => $code,
                    ':generatorId' => $generatorId,
                    ':batchId' => $batchId,
                    ':expiresAt' => $expiresAt
                ]
            );

            $codes[] = $code;
        }

        AdvancedFeatures::logAudit($generatorId, null, 'create_bulk_invites', 'invite_batch', $batchId,
            ['count' => $count, 'batchName' => $batchName, 'expirationDays' => $expirationDays]);

        return [
            'batchId' => $batchId,
            'count' => $count,
            'codes' => $codes,
            'expiresAt' => $expirationDays ? date('Y-m-d H:i:s', strtotime("+{$expirationDays} days")) : null
        ];
    }

    // ========================================
    // REVOKE INVITES
    // ========================================

    public static function revokeInvite($inviteId, $revokedBy) {
        $db = self::db();

        $db->query(
            "UPDATE inviteCodes SET isRevoked = 1, revokedBy = :revokedBy, revokedAt = NOW()
             WHERE id = :id",
            [':id' => $inviteId, ':revokedBy' => $revokedBy]
        );

        AdvancedFeatures::logAudit($revokedBy, null, 'revoke_invite', 'invite', $inviteId);
    }

    public static function revokeBatch($batchId, $revokedBy) {
        $db = self::db();

        // Revoke all codes in batch
        $db->query(
            "UPDATE inviteCodes SET isRevoked = 1, revokedBy = :revokedBy, revokedAt = NOW()
             WHERE batchId = :batchId AND isRevoked = 0",
            [':batchId' => $batchId, ':revokedBy' => $revokedBy]
        );

        // Update batch status
        $db->query(
            "UPDATE invite_batches SET codesRevoked = totalCodes WHERE id = :id",
            [':id' => $batchId]
        );

        AdvancedFeatures::logAudit($revokedBy, null, 'revoke_batch', 'invite_batch', $batchId);
    }

    // ========================================
    // EXPORT INVITES
    // ========================================

    public static function exportInviteCodes($batchId = null, $format = 'csv') {
        $db = self::db();

        if ($batchId) {
            $result = $db->query(
                "SELECT code, expiresAt, used FROM inviteCodes WHERE batchId = :batchId AND isRevoked = 0",
                [':batchId' => $batchId]
            );
        } else {
            $result = $db->query(
                "SELECT code, expiresAt, used FROM inviteCodes WHERE isRevoked = 0 ORDER BY createdAt DESC LIMIT 1000"
            );
        }

        if ($format === 'csv') {
            $csv = "Code,Expires,Used\n";
            foreach ($result as $row) {
                $csv .= "\"{$row['code']}\",\"{$row['expiresAt']}\",\"" . ($row['used'] ? 'Yes' : 'No') . "\"\n";
            }
            return $csv;
        }

        return $result;
    }

    // ========================================
    // HELPER FUNCTIONS
    // ========================================

    private static function generateUniqueCode() {
        $db = self::db();

        do {
            $code = self::generateRandomCode();
            $exists = $db->query(
                "SELECT id FROM inviteCodes WHERE code = :code",
                [':code' => $code]
            );
        } while (!empty($exists));

        return $code;
    }

    private static function generateRandomCode($length = 12) {
        // Generate readable alphanumeric codes (no confusing chars like 0/O, 1/l, etc.)
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[\random_int(0, \strlen($chars) - 1)];
        }

        // Format as XXX-XXX-XXX for readability
        return \substr($code, 0, 3) . '-' . \substr($code, 3, 3) . '-' . \substr($code, 6);
    }

    public static function getBatches($limit = 20, $offset = 0, $generatorId = null) {
        $db = self::db();

        $query = "SELECT b.*,
                         COUNT(i.id) as totalInvites,
                         SUM(CASE WHEN i.used = 1 THEN 1 ELSE 0 END) as usedInvites,
                         SUM(CASE WHEN i.isRevoked = 1 THEN 1 ELSE 0 END) as revokedInvites,
                         u.username as generatorName
                  FROM invite_batches b
                  LEFT JOIN inviteCodes i ON b.id = i.batchId
                  LEFT JOIN users u ON b.generatorId = u.id
                  WHERE 1=1";
        $params = [];

        if ($generatorId) {
            $query .= " AND b.generatorId = :generatorId";
            $params[':generatorId'] = $generatorId;
        }

        $query .= " GROUP BY b.id ORDER BY b.createdAt DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        return $db->query($query, $params);
    }

    public static function getBatch($batchId) {
        $db = self::db();

        $result = $db->query(
            "SELECT b.*,
                    COUNT(i.id) as totalInvites,
                    SUM(CASE WHEN i.used = 1 THEN 1 ELSE 0 END) as usedInvites,
                    SUM(CASE WHEN i.isRevoked = 1 THEN 1 ELSE 0 END) as revokedInvites,
                    u.username as generatorName
             FROM invite_batches b
             LEFT JOIN inviteCodes i ON b.id = i.batchId
             LEFT JOIN users u ON b.generatorId = u.id
             WHERE b.id = :id
             GROUP BY b.id",
            [':id' => $batchId]
        );

        return !empty($result) ? $result[0] : null;
    }
}
