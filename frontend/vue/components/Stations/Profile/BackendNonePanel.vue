<template>
    <section
        id="profile-backend"
        class="card"
        role="region"
        aria-labelledby="hdr_backend_disabled"
    >
        <div class="card-header bg-primary-dark">
            <h3
                id="hdr_backend_disabled"
                class="card-title"
            >
                {{ $gettext('AutoDJ Disabled') }}
            </h3>
        </div>
        <div class="card-body">
            <p class="card-text">
                {{
                    $gettext('AutoDJ has been disabled for this station. No music will automatically be played when a source is not live.')
                }}
            </p>
        </div>
    </section>
</template>
