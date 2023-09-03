<?php

use Sunlight\Database\Database as DB;
use Sunlight\User;

return function (array $args) {
    // comparison of the original text and the sent text
    if (strcmp($args['text'], $args['post']['text']) === 0) {
        return; // no changes - don't save
    }

    if ($args['post']['author'] != User::getId()) {
        $changeset = [
            'moderated_at' => time(),
            'moderated_by' => User::getId(),
        ];
    } else {
        $changeset = [
            'edited_at' => time(),
        ];
    }
    // increase edit counter
    $changeset['edit_count'] = DB::raw('edit_count+1');

    DB::update('post', 'id=' . DB::val($args['id']), $changeset);
};