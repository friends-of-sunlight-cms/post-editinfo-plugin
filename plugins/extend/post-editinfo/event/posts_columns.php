<?php

return function (array $args) {
    $args['output'] .= ',p.edited_at,p.edit_count,p.moderated_at,p.moderated_by';
};