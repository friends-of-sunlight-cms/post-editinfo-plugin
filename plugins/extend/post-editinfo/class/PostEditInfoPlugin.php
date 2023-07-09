<?php

namespace SunlightExtend\PostEditinfo;

use Sunlight\Database\Database as DB;
use Sunlight\Extend;
use Sunlight\Plugin\ExtendPlugin;
use Sunlight\Router;
use Sunlight\User;

class PostEditInfoPlugin extends ExtendPlugin
{
    public function onHead(array $args): void
    {
        $args['css'][] = $this->getAssetPath('public/css/posteditinfo.css');
    }

    public function onPostsColumns(array $args): void
    {
        $args['output'] .= ',p.edited_at,p.edit_count,p.moderated_at,p.moderated_by';
    }

    public function onPostsPost(array $args): void
    {
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
    }

    public function onPostEdit(array $args): void
    {
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
    }
}
