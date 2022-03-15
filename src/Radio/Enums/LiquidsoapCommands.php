<?php

// phpcs:ignoreFile

declare(strict_types=1);

namespace App\Radio\Enums;

use App\Radio\Backend\Liquidsoap\Command\AbstractCommand;
use App\Radio\Backend\Liquidsoap\Command\DjAuthCommand;
use App\Radio\Backend\Liquidsoap\Command\DjOffCommand;
use App\Radio\Backend\Liquidsoap\Command\DjOnCommand;
use App\Radio\Backend\Liquidsoap\Command\FeedbackCommand;
use App\Radio\Backend\Liquidsoap\Command\NextSongCommand;

enum LiquidsoapCommands: string
{
    case DjAuth = 'auth';
    case DjOn = 'djon';
    case DjOff = 'djoff';
    case Feedback = 'feedback';
    case NextSong = 'nextsong';

    /** @return class-string<AbstractCommand> */
    public function getClass()
    {
        return match ($this) {
            self::DjAuth => DjAuthCommand::class,
            self::DjOn => DjOnCommand::class,
            self::DjOff => DjOffCommand::class,
            self::Feedback => FeedbackCommand::class,
            self::NextSong => NextSongCommand::class
        };
    }
}
