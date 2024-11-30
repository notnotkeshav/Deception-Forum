DELIMITER //

CREATE PROCEDURE UpdateVoteAndGetCounts(
    IN commentId INT,
    IN voteType VARCHAR(10),
    IN userId INT
)
BEGIN
    DECLARE existing_vote_id INT;
    DECLARE existing_vote_type VARCHAR(10);

    -- Check if the user has already voted
    SELECT id, vote_type INTO existing_vote_id, existing_vote_type
    FROM comment_votes 
    WHERE comment_id = commentId AND user_id = userId;

    IF existing_vote_id IS NOT NULL THEN
        -- If the vote is the same as the new vote, remove it
        IF existing_vote_type = voteType THEN
            DELETE FROM comment_votes WHERE id = existing_vote_id;
        ELSE
            -- Update the vote to the new type
            UPDATE comment_votes 
            SET vote_type = voteType 
            WHERE id = existing_vote_id;
        END IF;
    ELSE
        -- Insert a new vote if no existing vote
        INSERT INTO comment_votes (comment_id, user_id, vote_type)
        VALUES (commentId, userId, voteType);
    END IF;

    -- Update the counts in comments table
    IF voteType = 'upvote' THEN
        UPDATE comments
        SET upvoteCount = (SELECT COUNT(*) FROM comment_votes WHERE comment_id = commentId AND vote_type = 'upvote'),
            downvoteCount = (SELECT COUNT(*) FROM comment_votes WHERE comment_id = commentId AND vote_type = 'downvote')
        WHERE id = commentId;
    ELSEIF voteType = 'downvote' THEN
        UPDATE comments
        SET upvoteCount = (SELECT COUNT(*) FROM comment_votes WHERE comment_id = commentId AND vote_type = 'upvote'),
            downvoteCount = (SELECT COUNT(*) FROM comment_votes WHERE comment_id = commentId AND vote_type = 'downvote')
        WHERE id = commentId;
    END IF;

    -- Return the updated counts
    SELECT upvoteCount, downvoteCount
    FROM comments
    WHERE id = commentId;
END //

DELIMITER ;
