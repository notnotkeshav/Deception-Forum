DELIMITER //

CREATE PROCEDURE UpdateCommentVotesAndGetCounts(
    IN commentId CHAR(36),
    IN voteType ENUM('upvote', 'downvote'),
    IN userId CHAR(36)
)
BEGIN
    DECLARE existingVoteId CHAR(36);
    DECLARE existingVoteType ENUM('upvote', 'downvote');

    -- Check if the user has already voted
    SELECT id, vote_type INTO existingVoteId, existingVoteType
    FROM comment_votes 
    WHERE comment_id = commentId AND user_id = userId;

    IF existingVoteId IS NOT NULL THEN
        -- If the vote is the same as the new vote, remove it
        IF existingVoteType = voteType THEN
            DELETE FROM comment_votes WHERE id = existingVoteId;
        ELSE
            -- Update the vote to the new type
            UPDATE comment_votes 
            SET vote_type = voteType 
            WHERE id = existingVoteId;
        END IF;
    ELSE
        -- Insert a new vote if no existing vote
        INSERT INTO comment_votes (comment_id, user_id, vote_type)
        VALUES (commentId, userId, voteType);
    END IF;

    -- Update the counts in comments table
    UPDATE comments
    SET upvoteCount = (SELECT COUNT(*) FROM comment_votes WHERE comment_id = commentId AND vote_type = 'upvote'),
        downvoteCount = (SELECT COUNT(*) FROM comment_votes WHERE comment_id = commentId AND vote_type = 'downvote')
    WHERE id = commentId;

    -- Return the updated counts
    SELECT upvoteCount, downvoteCount
    FROM comments
    WHERE id = commentId;
END //

DELIMITER ;

DELIMITER //

CREATE PROCEDURE UpdateThreadVotesAndGetCounts(
    IN threadId CHAR(36),
    IN voteType ENUM('upvote', 'downvote'),
    IN userId CHAR(36)
)
BEGIN
    DECLARE existingVoteId CHAR(36);
    DECLARE existingVoteType ENUM('upvote', 'downvote');

    -- Check if the user has already voted
    SELECT id, vote_type INTO existingVoteId, existingVoteType
    FROM thread_votes 
    WHERE thread_id = threadId AND user_id = userId;

    IF existingVoteId IS NOT NULL THEN
        -- If the vote is the same as the new vote, remove it
        IF existingVoteType = voteType THEN
            DELETE FROM thread_votes WHERE id = existingVoteId;
        ELSE
            -- Update the vote to the new type
            UPDATE thread_votes 
            SET vote_type = voteType 
            WHERE id = existingVoteId;
        END IF;
    ELSE
        -- Insert a new vote if no existing vote
        INSERT INTO thread_votes (thread_id, user_id, vote_type)
        VALUES (threadId, userId, voteType);
    END IF;

    -- Update the counts in threads table
    UPDATE threads
    SET upvoteCount = (SELECT COUNT(*) FROM thread_votes WHERE thread_id = threadId AND vote_type = 'upvote'),
        downvoteCount = (SELECT COUNT(*) FROM thread_votes WHERE thread_id = threadId AND vote_type = 'downvote')
    WHERE id = threadId;

    -- Return the updated counts
    SELECT upvoteCount, downvoteCount
    FROM threads
    WHERE id = threadId;
END //

DELIMITER ;
