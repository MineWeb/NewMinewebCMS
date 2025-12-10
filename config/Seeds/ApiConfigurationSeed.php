<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

class ApiConfigurationSeed extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'skins' => 0,
                'skin_filename' => 'skins/{PLAYER}_skin',
                'skin_free' => 0,
                'skin_width' => 64,
                'skin_height' => 32,
                'capes' => 0,
                'cape_filename' => 'skins/capes/{PLAYER}_cape',
                'cape_free' => 0,
                'cape_width' => 64,
                'cape_height' => 32,
                'get_premium_skins' => 1,
                'use_skin_restorer' => 0,
                'skin_restorer_server_id' => 0,
            ],
        ];

        $this->table('api_configurations')
            ->insert($data)
            ->saveData();
    }
}
