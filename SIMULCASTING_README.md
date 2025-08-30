# AzuraCast Simulcasting Feature

## Overview

The Simulcasting feature allows AzuraCast stations to stream their audio content to multiple social media platforms simultaneously, including Facebook Live and YouTube Live. This feature integrates with LiquidSoap to provide robust, fail-safe streaming capabilities.

## Features

- **Multi-Platform Support**: Stream to Facebook Live and YouTube Live
- **Fail-Safe Operation**: Individual platform failures don't affect the main station stream
- **Real-Time Status Monitoring**: Live status updates for each simulcasting stream
- **Easy Management**: User-friendly interface for creating, editing, and managing streams
- **LiquidSoap Integration**: Seamless integration with existing LiquidSoap backend

## Requirements

- **Backend**: Must use LiquidSoap backend (`backend_type = liquidsoap`)
- **Permissions**: Requires `Broadcasting` permission to manage simulcasting streams
- **Dependencies**: FFmpeg with video encoding support (for video streams)

## Architecture

### Backend Components

#### 1. Entity Layer
- **`Simulcasting`**: Main entity for simulcasting streams
- **`SimulcastingStatus`**: Enum for stream statuses (stopped, running, error, etc.)

#### 2. Service Layer
- **`SimulcastingManager`**: Core service for managing simulcasting operations
- **`AbstractSimulcastingAdapter`**: Base class for platform-specific adapters
- **`FacebookSimulcastingAdapter`**: Facebook Live streaming implementation
- **`YouTubeSimulcastingAdapter`**: YouTube Live streaming implementation

#### 3. API Layer
- **`SimulcastingController`**: RESTful API endpoints for CRUD operations
- **Routes**: `/api/station/{station_id}/simulcasting/*`

### Frontend Components

#### 1. Main Interface
- **`Simulcasting.vue`**: Main management page with table view
- **`SimulcastingModal.vue`**: Create/edit modal for streams

#### 2. Navigation
- **Menu Item**: Added to Broadcasting menu (only visible with LiquidSoap backend)
- **Route**: `/station/{station_id}/simulcasting`

## Database Schema

```sql
CREATE TABLE station_simulcasting (
    id INT AUTO_INCREMENT NOT NULL,
    station_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    adapter VARCHAR(50) NOT NULL,
    stream_key VARCHAR(500) NOT NULL,
    status VARCHAR(20) NOT NULL,
    error_message LONGTEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY(id),
    FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE
);
```

## API Endpoints

### List Simulcasting Streams
```
GET /api/station/{station_id}/simulcasting
```

### Create Stream
```
POST /api/station/{station_id}/simulcasting
{
    "name": "My Facebook Stream",
    "adapter": "facebook",
    "stream_key": "your_stream_key_here"
}
```

### Get Stream
```
GET /api/station/{station_id}/simulcasting/{id}
```

### Update Stream
```
PUT /api/station/{station_id}/simulcasting/{id}
{
    "name": "Updated Name",
    "stream_key": "new_stream_key"
}
```

### Delete Stream
```
DELETE /api/station/{station_id}/simulcasting/{id}
```

### Start Stream
```
POST /api/station/{station_id}/simulcasting/{id}/start
```

### Stop Stream
```
POST /api/station/{station_id}/simulcasting/{id}/stop
```

### Get Available Adapters
```
GET /api/station/{station_id}/simulcasting/adapters
```

## LiquidSoap Integration

The feature generates LiquidSoap configuration that integrates with the existing station setup. Each adapter provides its own LiquidSoap output configuration:

### Facebook Live (RTMPS)
```liquidsoap
# Facebook Live (RTMPS)
fb_url = "rtmps://live-api-s.facebook.com:443/rtmp/your_stream_key"
output.url(
    url=fb_url,
    fallible=true,
    %ffmpeg(
        format="flv",
        %video.raw(
            codec="libx264",
            pixel_format="yuv420p",
            b="2500k",
            preset="veryfast",
            r=30,
            g=60
        ),
        %audio(
            codec="aac",
            samplerate=44100,
            channels=2,
            b="128k",
            profile="aac_low"
        )
    ),
    videostream
)
```

### YouTube Live (HLS)
```liquidsoap
# YouTube Live (HLS ingest)
output.youtube.live.hls(
    key="your_stream_key",
    fallible=true,
    %ffmpeg(
        format="mpegts",
        %video.raw(
            codec="libx264",
            pixel_format="yuv420p",
            b="2500k",
            preset="veryfast",
            r=30,
            g=60
        ),
        %audio(
            codec="aac",
            samplerate=44100,
            channels=2,
            b="128k",
            profile="aac_low"
        )
    ),
    videostream
)
```

## Status Management

### Status Flow
1. **Stopped** → **Starting** → **Running**
2. **Running** → **Stopping** → **Stopped**
3. **Any Status** → **Error** (on failure)

### Error Handling
- Failed streams are marked with `Error` status
- Error messages are stored and displayed to users
- Manual intervention required to restart failed streams
- No automatic retry to prevent resource waste

## Installation & Setup

### 1. Database Migration
Run the migration to create the simulcasting table:
```bash
cd backend
php bin/console doctrine:migrations:migrate
```

### 2. Clear Cache
Clear application cache after installation:
```bash
cd backend
php bin/console cache:clear
```

### 3. Frontend Build
Build the frontend assets:
```bash
cd frontend
npm run build
```

## Usage

### Creating a Simulcasting Stream

1. Navigate to **Stations** → **Your Station** → **Broadcasting** → **Simulcasting**
2. Click **"Add Simulcasting Stream"**
3. Fill in the form:
   - **Name**: Descriptive name for the stream
   - **Platform**: Select Facebook or YouTube
   - **Stream Key**: Platform-provided stream key
4. Click **"Create"**

### Managing Streams

- **Start/Stop**: Use the Start/Stop buttons to control individual streams
- **Edit**: Click Edit to modify stream configuration
- **Delete**: Remove streams that are no longer needed
- **Monitor**: View real-time status and error messages

### Platform Setup

#### Facebook Live
1. Go to Facebook Creator Studio
2. Create a new Live Video
3. Copy the Stream Key
4. Use the Stream Key in AzuraCast

#### YouTube Live
1. Go to YouTube Studio
2. Create a new Live Stream
3. Copy the Stream Key
4. Use the Stream Key in AzuraCast

## Configuration

### Adapter Configuration
Each adapter can be configured with platform-specific settings:

```php
// Facebook Adapter
'video_bitrate' => '2500k',
'audio_bitrate' => '128k',
'fps' => 30,
'gop' => 60

// YouTube Adapter
'video_bitrate' => '2500k',
'audio_bitrate' => '128k',
'fps' => 30,
'gop' => 60
```

### LiquidSoap Integration
The feature automatically integrates with existing LiquidSoap configuration:
- Streams are added as fallible outputs
- Video streams include looping background video
- Now Playing information is overlaid on video
- Audio is synchronized with the main station stream

## Troubleshooting

### Common Issues

#### Stream Won't Start
- Verify the stream key is correct
- Check that the station uses LiquidSoap backend
- Ensure FFmpeg supports required codecs
- Check station logs for error messages

#### Video Quality Issues
- Adjust video bitrate in adapter configuration
- Verify FFmpeg preset settings
- Check network bandwidth for streaming

#### Platform-Specific Errors
- Facebook: Verify RTMPS stream key format
- YouTube: Ensure HLS ingest is enabled
- Check platform-specific requirements

### Logs
Check the following logs for debugging:
- **Application Logs**: `backend/logs/app.log`
- **LiquidSoap Logs**: `backend/logs/liquidsoap.log`
- **Station Logs**: Available in station management interface

## Development

### Adding New Adapters

1. Create a new adapter class extending `AbstractSimulcastingAdapter`
2. Implement required methods:
   - `getStreamKey()`
   - `getStatus()`
   - `run()`
   - `stop()`
   - `getAdapterName()`
   - `getAdapterDescription()`
   - `getConfiguration()`
   - `getLiquidsoapOutput()`

3. Add the adapter to `SimulcastingManager::ADAPTER_MAP`

### Testing
```bash
# Backend tests
cd backend
composer run codeception

# Frontend tests
cd frontend
npm run test
```

## Security Considerations

- Stream keys are stored encrypted in the database
- Access is restricted to users with Broadcasting permissions
- API endpoints require proper authentication
- No automatic retry to prevent abuse

## Performance Impact

- Minimal impact on main station stream
- Video encoding adds CPU overhead
- Network bandwidth usage scales with video quality
- Fallible outputs prevent cascading failures

## Future Enhancements

- **Additional Platforms**: Twitch, Instagram Live, TikTok
- **Advanced Video**: Custom overlays, branding, transitions
- **Analytics**: Stream performance metrics
- **Scheduling**: Automated stream start/stop times
- **Multi-Resolution**: Adaptive bitrate streaming

## Support

For issues and questions:
- Check the troubleshooting section above
- Review application logs
- Consult AzuraCast documentation
- Open an issue on the project repository

## License

This feature is part of AzuraCast and follows the same license terms.

