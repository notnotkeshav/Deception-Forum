-- PROCEDURE #1

DELIMITER //

CREATE PROCEDURE updateCommentVotesAndGetCounts(
    IN p_commentId CHAR(36),
    IN p_voteType VARCHAR(10),
    IN p_userId CHAR(36)
)
BEGIN
    DECLARE existingVoteId CHAR(36);
    DECLARE existingVoteType VARCHAR(10);

    -- Check if the user has already voted
    SELECT id, voteType INTO existingVoteId, existingVoteType
    FROM commentVotes 
    WHERE commentId = p_commentId AND userId = p_userId;

    IF existingVoteId IS NOT NULL THEN
        -- If the vote is the same as the new vote, remove it
        IF existingVoteType = p_voteType THEN
            DELETE FROM commentVotes WHERE id = existingVoteId;
        ELSE
            -- Update the vote to the new type
            UPDATE commentVotes 
            SET voteType = p_voteType 
            WHERE id = existingVoteId;
        END IF;
    ELSE
        -- Insert a new vote if no existing vote
        INSERT INTO commentVotes (commentId, userId, voteType)
        VALUES (p_commentId, p_userId, p_voteType);
    END IF;

    -- Update the counts in comments table
    UPDATE comments
        SET upvoteCount = (SELECT COUNT(*) FROM commentVotes WHERE commentId = p_commentId AND voteType = 'upvote'),
            downvoteCount = (SELECT COUNT(*) FROM commentVotes WHERE commentId = p_commentId AND voteType = 'downvote')
        WHERE id = p_commentId;

    -- Return the updated counts
    SELECT upvoteCount, downvoteCount
    FROM comments
    WHERE id = p_commentId;
END //

DELIMITER ;


-- PROCEDURE #2

DELIMITER //

CREATE PROCEDURE updateThreadVotesAndGetCounts(
    IN p_threadId CHAR(36),
    IN p_voteType VARCHAR(10),
    IN p_userId CHAR(36)
)
BEGIN
    DECLARE existingVoteId CHAR(36);
    DECLARE existingVoteType VARCHAR(10);

    -- Check if the user has already voted
    SELECT id, voteType INTO existingVoteId, existingVoteType
    FROM threadVotes 
    WHERE threadId = p_threadId AND userId = p_userId;

    IF existingVoteId IS NOT NULL THEN
        -- If the vote is different, update it 
        IF existingVoteType != p_voteType THEN
            UPDATE threadVotes 
            SET voteType = p_voteType 
            WHERE id = existingVoteId;
        ELSE
            -- If the vote is the same, remove it
            DELETE FROM threadVotes WHERE id = existingVoteId;
        END IF;
    ELSE
        -- Insert a new vote if no existing vote
        INSERT INTO threadVotes (threadId, userId, voteType)
        VALUES (p_threadId, p_userId, p_voteType);
    END IF;

    -- Update the counts in threads table
    UPDATE threads
        SET upvoteCount = (SELECT COUNT(*) FROM threadVotes WHERE threadId = p_threadId AND voteType = 'upvote'),
            downvoteCount = (SELECT COUNT(*) FROM threadVotes WHERE threadId = p_threadId AND voteType = 'downvote')
        WHERE id = p_threadId;

    -- Return the updated counts
    SELECT upvoteCount, downvoteCount
    FROM threads
    WHERE id = p_threadId;
END //

DELIMITER ;


-- PROCEDURE #3

DELIMITER //

CREATE PROCEDURE updateMessageVotesAndGetCounts(
    IN p_messageId CHAR(36),
    IN p_voteType VARCHAR(10),
    IN p_userId CHAR(36)
)
BEGIN
    DECLARE existingVoteId CHAR(36);
    DECLARE existingVoteType VARCHAR(10);

    -- Check if the user has already voted
    SELECT id, voteType INTO existingVoteId, existingVoteType
    FROM groupMessageVotes 
    WHERE messageId = p_messageId AND userId = p_userId;

    IF existingVoteId IS NOT NULL THEN
        -- If the vote is different, update it
        IF existingVoteType != p_voteType THEN
            UPDATE groupMessageVotes 
            SET voteType = p_voteType 
            WHERE id = existingVoteId;
        ELSE
            -- If the vote is the same, remove it
            DELETE FROM groupMessageVotes WHERE id = existingVoteId;
        END IF;
    ELSE
        -- Insert a new vote if no existing vote
        INSERT INTO groupMessageVotes (messageId, userId, voteType)
        VALUES (p_messageId, p_userId, p_voteType);
    END IF;

    -- Update the counts in groupMessages table
    UPDATE groupMessages
        SET upvoteCount = (SELECT COUNT(*) FROM groupMessageVotes WHERE messageId = p_messageId AND voteType = 'upvote'),
            downvoteCount = (SELECT COUNT(*) FROM groupMessageVotes WHERE messageId = p_messageId AND voteType = 'downvote')
        WHERE id = p_messageId;

    -- Return the updated counts
    SELECT upvoteCount, downvoteCount
    FROM groupMessages
    WHERE id = p_messageId;
END //

DELIMITER ;

-- PROCEDURE #4

DELIMITER //

CREATE PROCEDURE updatePrivateMessageVotesAndGetCounts(
    IN p_messageId CHAR(36),
    IN p_voteType VARCHAR(10),
    IN p_userId CHAR(36)
)
BEGIN
    DECLARE existingVoteId CHAR(36);
    DECLARE existingVoteType VARCHAR(10);

    -- Check if the user has already voted
    SELECT id, voteType INTO existingVoteId, existingVoteType
    FROM privateChatVotes 
    WHERE messageId = p_messageId AND userId = p_userId;

    IF existingVoteId IS NOT NULL THEN
        -- If the vote is different, update it
        IF existingVoteType != p_voteType THEN
            UPDATE privateChatVotes 
            SET voteType = p_voteType 
            WHERE id = existingVoteId;
        ELSE
            -- If the vote is the same, remove it
            DELETE FROM privateChatVotes WHERE id = existingVoteId;
        END IF;
    ELSE
        -- Insert a new vote if no existing vote
        INSERT INTO privateChatVotes (messageId, userId, voteType)
        VALUES (p_messageId, p_userId, p_voteType);
    END IF;

    -- Update the counts in privateChatMessages table
    UPDATE privateChatMessages
        SET upvoteCount = (SELECT COUNT(*) FROM privateChatVotes WHERE messageId = p_messageId AND voteType = 'upvote'),
            downvoteCount = (SELECT COUNT(*) FROM privateChatVotes WHERE messageId = p_messageId AND voteType = 'downvote')
        WHERE id = p_messageId;

    -- Return the updated counts
    SELECT upvoteCount, downvoteCount
    FROM privateChatMessages
    WHERE id = p_messageId;
END //

DELIMITER ;
