<?php

use Sunlight\Database\Database as DB;
use Sunlight\Extend;
use Sunlight\Router;
use Sunlight\User;

return function (array $args) {
    // check state
    $isEdited = isset($args['item']['edited_at']);
    $isModerated = isset($args['item']['moderated_at']);

    $info = [
        'is_edited' => $isEdited,
        'is_moderated' => $isModerated,
        'moderator' => null, // moderator name or link to the moderator
        'messages' => [],
    ];

    // event
    $output = Extend::buffer('post-editinfo.before', [
        'item' => $args['item'],
        'info' => &$info,
    ]);

    // add messages
    if ($info['is_edited']) {
        $info['messages']['edited'] = _lang('posteditinfo.post.edited', [
            '%time%' => DB::datetime($args['item']['edited_at'])
        ]);
    }
    if ($info['is_moderated']) {
        if (empty($info['moderator'])) {
            $userQuery = User::createQuery();
            $modData = DB::queryRow("SELECT " . $userQuery['column_list'] . " FROM " . DB::table('user') . " u " . $userQuery['joins'] . " WHERE u.id=" . DB::val($args['item']['moderated_by']));
            if ($modData !== false) {
                $info['moderator'] = Router::userFromQuery($userQuery, $modData);
            }
        }
        $info['messages']['moderated'] = _lang('posteditinfo.post.moderated', [
            '%user%' => $info['moderator'],
            '%time%' => DB::datetime($args['item']['edited_at'])
        ]);
    }

    // compose output
    if (count($info['messages']) > 0) {
        $totalEdited = _lang('posteditinfo.post.total_edited', ['%count%' => $args['item']['edit_count'],]);
        $output .= '<ul class="posteditinfo" title="' . $totalEdited . '">';
        foreach ($info['messages'] as $message) {
            $output .= '<li>' . $message . '</li>';
        }
        $output .= '</ul>';
    }

    // event
    $output .= Extend::buffer('post-editinfo.after', [
        'item' => $args['item'],
        'info' => $info,
        'output' => &$output,
    ]);

    // render
    $args['item']['text'] .= $output;
};
