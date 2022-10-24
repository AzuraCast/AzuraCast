<?php

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
            self::Artist->value => __('Artist'),
            self::Bpm->value => __('BPM'),
            self::Comment->value => __('Comment'),
            self::Composer->value => __('Composer'),
            self::Copyright->value => __('Copyright'),
            self::EncodedBy->value => __('Encoded By'),
            self::Genre->value => __('Genre'),
            self::Isrc->value => __('ISRC'),
            self::Title->value => __('Title'),
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
