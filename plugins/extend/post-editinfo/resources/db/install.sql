ALTER TABLE `sunlight_post`
    ADD `edited_at` int(11) DEFAULT NULL,
    ADD `edit_count` int(11) DEFAULT 0,
    ADD `moderated_at` int(11) DEFAULT NULL,
    ADD `moderated_by` int(11) DEFAULT NULL
;