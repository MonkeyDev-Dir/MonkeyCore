<?php

namespace App\Services;

use Composer\InstalledVersions;
use DiceBear\Avatar;
use DiceBear\Style;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarService
{
    private ?Style $style = null;

    public function generateRobotAvatar(): string
    {
        $path = 'avatars/'.Str::uuid().'.svg';

        return $this->createRobotAvatar((string) Str::uuid(), $path);
    }

    /**
     * @return array<int, string>
     */
    public function generateRobotAvatars(): array
    {
        return collect(range(1, 10))
            ->map(function (int $number): string {
                $path = "avatars/options/robot-{$number}.svg";

                return $this->createRobotAvatar("robot-option-{$number}", $path);
            })
            ->all();
    }

    private function createRobotAvatar(string $seed, string $path): string
    {
        if ($this->disk()->exists($path)) {
            return $path;
        }

        $avatar = new Avatar($this->style(), [
            'seed' => $seed,
            'size' => 256,
        ]);

        $this->disk()->put($path, (string) $avatar);

        return $path;
    }

    private function style(): Style
    {
        if ($this->style instanceof Style) {
            return $this->style;
        }

        $stylePath = InstalledVersions::getInstallPath('dicebear/styles').'/src/bottts-neutral.json';

        return $this->style = Style::fromJson((string) file_get_contents($stylePath));
    }

    private function disk(): FilesystemAdapter
    {
        return Storage::disk('public');
    }
}
