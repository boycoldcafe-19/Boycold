-- A full loyalty card stays Active. Reward eligibility is based on its
-- ten-stamp balance and is displayed as "Ready to be Claimed" in the reward column.
UPDATE `users`
SET `loyalty_card_status` = 'active'
WHERE `loyalty_card_status` = 'completed';
