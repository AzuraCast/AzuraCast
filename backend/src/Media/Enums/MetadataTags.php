<?php

declare(strict_types=1);

namespace App\Media\Enums;

enum MetadataTags: string
{
    case Album = 'album'; // TAL, TALB
    case Artist = 'artist'; // TP1, TPE1
    case Bpm = 'bpm'; // TBP, TBPM
    case Comment = 'comment'; // COM, COMM
    case Composer = 'composer'; // TCM, TCOM
    case Copyright = 'copyright'; // WCOP, WCP
    case EncodedBy = 'encoded_by'; // TEN, TENC
    case Genre = 'genre'; // TCO, TCON
    case Isrc = 'isrc'; // TRC, TSRC
    case Title = 'title'; // TIT2, TT2
    case Year = 'year'; // TYE, TYER

    // Other possible metadata tags that may not be pulled by FFProbe
    case AlbumArtist = 'album_artist';
    case AlbumArtistSortOrder = 'album_artist_sort_order'; // TS2, TSO2
    case AlbumSortOrder = 'album_sort_order'; // TSA, TSOA
    case Band = 'band'; // TP2, TPE2
    case CommercialInformation = 'commercial_information'; // WCM, WCOM
    case ComposerSortOrder = 'composer_sort_order'; // TSC, TSOC
    case Conductor = 'conductor'; // TP3, TPE3
    case ContentGroupDescription = 'content_group_description'; // TIT1, TT1
    case CopyrightMessage = 'copyright_message'; // TCOP, TCR
    case EncoderSettings = 'encoder_settings'; // TSS, TSSE
    case EncodingTime = 'encoding_time'; // TDEN
    case FileOwner = 'file_owner'; // TOWN
    case FileType = 'file_type'; // TFLT, TFT
    case InitialKey = 'initial_key'; // TKE, TKEY
    case InternetRadioStationName = 'internet_radio_station_name'; // TRSN
    case InternetRadioStationOwner = 'internet_radio_station_owner'; // TRSO
    case InvolvedPeopleList = 'involved_people_list'; // IPL, IPLS, TIPL
    case Language = 'language'; // TLA, TLAN
    case Length = 'length'; // TLE, TLEN
    case LinkedInformation = 'linked_information'; // LINK, LNK
    case Lyricist = 'lyricist'; // TEXT, TXT
    case MediaType = 'media_type'; // TMED, TMT
    case Mood = 'mood'; // TMOO
    case MusicCdIdentifier = 'music_cd_identifier'; // MCDI, MCI
    case MusicianCreditsList = 'musician_credits_list'; // TMCL
    case OriginalAlbum = 'original_album'; // TOAL, TOT
    case OriginalArtist = 'original_artist'; // TOA, TOPE
    case OriginalFilename = 'original_filename'; // TOF, TOFN
    case OriginalLyricist = 'original_lyricist'; // TOL, TOLY
    case OriginalReleaseTime = 'original_release_time'; // TDOR
    case OriginalYear = 'original_year'; // TOR, TORY
    case PartOfACompilation = 'part_of_a_compilation'; // TCMP, TCP
    case PartOfASet = 'part_of_a_set'; // TPA, TPOS
    case PerformerSortOrder = 'performer_sort_order'; // TSOP, TSP
    case PlaylistDelay = 'playlist_delay'; // TDLY, TDY
    case ProducedNotice = 'produced_notice'; // TPRO
    case Publisher = 'publisher'; // TPB, TPUB
    case RecordingTime = 'recording_time'; // TDRC
    case ReleaseTime = 'release_time'; // TDRL
    case Remixer = 'remixer'; // TP4, TPE4
    case SetSubtitle = 'set_subtitle'; // TSST
    case Subtitle = 'subtitle'; // TIT3, TT3
    case TaggingTime = 'tagging_time'; // TDTG
    case TermsOfUse = 'terms_of_use'; // USER
    case TitleSortOrder = 'title_sort_order'; // TSOT, TST
    case TrackNumber = 'track_number'; // TRCK, TRK
    case UnsynchronisedLyric = 'unsynchronised_lyric'; // ULT, USLT
    case UrlArtist = 'url_artist'; // WAR, WOAR
    case UrlFile = 'url_file'; // WAF, WOAF
    case UrlPayment = 'url_payment'; // WPAY
    case UrlPublisher = 'url_publisher'; // WPB, WPUB
    case UrlSource = 'url_source'; // WAS, WOAS
    case UrlStation = 'url_station'; // WORS
    case UrlUser = 'url_user'; // WXX, WXXX

    public static function getNames(): array
    {
        return [
            self::Album->value => __('Album'),
            self::AlbumArtist->value => __('Album Artist'),
            self::AlbumArtistSortOrder->value => __('Album Artist Sort Order'), // TS2, TSO2
            self::AlbumSortOrder->value => __('Album Sort Order'), // TSA, TSOA
            self::Artist->value => __('Artist'),
            self::Band->value => __('Band'), // TP2, TPE2
            self::Bpm->value => __('BPM'),
            self::Comment->value => __('Comment'),
            self::CommercialInformation->value => __('Commercial Information'), // WCM, WCOM
            self::Composer->value => __('Composer'),
            self::ComposerSortOrder->value => __('Composer Sort Order'), // TSC, TSOC
            self::Conductor->value => __('Conductor'), // TP3, TPE3
            self::ContentGroupDescription->value => __('Content Group Description'), // TIT1, TT1
            self::Copyright->value => __('Copyright'),
            self::CopyrightMessage->value => __('Copyright Message'), // TCOP, TCR
            self::EncodedBy->value => __('Encoded By'),
            self::EncoderSettings->value => __('Encoder Settings'), // TSS, TSSE
            self::EncodingTime->value => __('Encoding Time'), // TDEN
            self::FileOwner->value => __('File Owner'), // TOWN
            self::FileType->value => __('File Type'), // TFLT, TFT
            self::Genre->value => __('Genre'),
            self::InitialKey->value => __('Initial Key'), // TKE, TKEY
            self::InternetRadioStationName->value => __('Internet Radio Station Name'), // TRSN
            self::InternetRadioStationOwner->value => __('Internet Radio Station Owner'), // TRSO
            self::InvolvedPeopleList->value => __('Involved People List'), // IPL, IPLS, TIPL
            self::Isrc->value => __('ISRC'),
            self::Language->value => __('Language'), // TLA, TLAN
            self::Length->value => __('Length'), // TLE, TLEN
            self::LinkedInformation->value => __('Linked Information'), // LINK, LNK
            self::Lyricist->value => __('Lyricist'), // TEXT, TXT
            self::MediaType->value => __('Media Type'), // TMED, TMT
            self::Mood->value => __('Mood'), // TMOO
            self::MusicCdIdentifier->value => __('Music CD Identifier'), // MCDI, MCI
            self::MusicianCreditsList->value => __('Musician Credits List'), // TMCL
            self::OriginalAlbum->value => __('Original Album'), // TOAL, TOT
            self::OriginalArtist->value => __('Original Artist'), // TOA, TOPE
            self::OriginalFilename->value => __('Original Filename'), // TOF, TOFN
            self::OriginalLyricist->value => __('Original Lyricist'), // TOL, TOLY
            self::OriginalReleaseTime->value => __('Original Release Time'), // TDOR
            self::OriginalYear->value => __('Original Year'), // TOR, TORY
            self::PartOfACompilation->value => __('Part of a Compilation'), // TCMP, TCP
            self::PartOfASet->value => __('Part of a Set'), // TPA, TPOS
            self::PerformerSortOrder->value => __('Performer Sort Order'), // TSOP, TSP
            self::PlaylistDelay->value => __('Playlist Delay'), // TDLY, TDY
            self::ProducedNotice->value => __('Produced Notice'), // TPRO
            self::Publisher->value => __('Publisher'), // TPB, TPUB
            self::RecordingTime->value => __('Recording Time'), // TDRC
            self::ReleaseTime->value => __('Release Time'), // TDRL
            self::Remixer->value => __('Remixer'), // TP4, TPE4
            self::SetSubtitle->value => __('Set Subtitle'), // TSST
            self::Subtitle->value => __('Subtitle'), // TIT3, TT3
            self::TaggingTime->value => __('Tagging Time'), // TDTG
            self::TermsOfUse->value => __('Terms of Use'), // USER
            self::Title->value => __('Title'),
            self::TitleSortOrder->value => __('Title Sort Order'), // TSOT, TST
            self::TrackNumber->value => __('Track Number'), // TRCK, TRK
            self::UnsynchronisedLyric->value => __('Unsynchronised Lyrics'), // ULT, USLT
            self::UrlArtist->value => __('URL Artist'), // WAR, WOAR
            self::UrlFile->value => __('URL File'), // WAF, WOAF
            self::UrlPayment->value => __('URL Payment'), // WPAY
            self::UrlPublisher->value => __('URL Publisher'), // WPB, WPUB
            self::UrlSource->value => __('URL Source'), // WAS, WOAS
            self::UrlStation->value => __('URL Station'), // WORS
            self::UrlUser->value => __('URL User'), // WXX, WXXX
            self::Year->value => __('Year'),
        ];
    }

    public static function getTag(string $value): ?self
    {
        $value = str_replace('-', '_', strtolower($value));

        $tag = self::tryFrom($value);
        if (null !== $tag) {
            return $tag;
        }

        $aliases = [
            'date' => self::Year,
            'encoder' => self::EncodedBy,
        ];

        return $aliases[$value] ?? null;
    }
}
