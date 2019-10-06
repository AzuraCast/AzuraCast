<template>
    <div>
        <div id="upload_progress"></div>
        <div id="file_drop_target">
            <translate>Drag files here to upload to this folder or</translate>
            <button id="file_browse_target" class="file-upload btn btn-primary text-center ml-1" type="button">
                <translate>Select File</translate>
                <i class="material-icons" aria-hidden="true">cloud_upload</i>
            </button>
            <small class="file-name"></small>
            <input type="file" accept=".mp3,.ogg,.wav,.aac,.mp4,.m4a,.flac,audio/*" multiple
                   style="visibility: hidden; position: absolute;"/>
        </div>
    </div>
</template>

