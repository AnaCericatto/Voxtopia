<?php

return [
    'errors' => [
        'data_invalid' => 'The build data is not in the expected format.',
        'version_unsupported' => 'This build was saved with a format version that is not supported.',
        'size_invalid' => 'The build size is not valid.',
        'palette_invalid' => 'The palette is not valid: it must list up to :limit different pieces.',
        'palette_piece_unavailable' => 'Palette entry :number uses a piece that does not exist or is not available to you.',
        'too_many_blocks' => 'The build has more blocks than the limit allows.',
        'block_malformed' => 'Block :number is not valid.',
        'block_out_of_bounds' => 'Block :number is outside the build area.',
        'palette_index_out_of_range' => 'Block :number uses a color that is not in the palette.',
        'rotation_invalid' => 'Block :number has an invalid rotation.',
        'step_out_of_range' => 'Block :number belongs to a step that does not exist.',
        'block_overlap' => 'Block :number is in a cell that is already used.',
    ],
];
