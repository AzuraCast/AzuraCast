<?php

declare(strict_types=1);

namespace App\Radio\Enums;

use App\Radio\Backend\Liquidsoap\Command;

enum LiquidsoapCommands: string
{
    case Copy = 'cp';
    case DjAuth = 'auth';
    case DjOn = 'djon';
    case DjOff = 'djoff';
    case Feedback = 'feedback';
    case NextSong = 'nextsong';

    /** @return class-string<Command\AbstractCommand> */
    public function getClass(): string
    {
        return match ($this) {
            self::Copy => Command\CopyCommand::class,
            self::DjAuth => Command\DjAuthCommand::class,
            self::DjOn => Command\DjOnCommand::class,
            self::DjOff => Command\DjOffCommand::class,
            self::Feedback => Command\FeedbackCommand::class,
            self::NextSong => Command\NextSongCommand::class
        };
    }
}
