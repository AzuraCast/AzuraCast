<template>
    <section
        id="profile-now-playing"
        class="card"
        role="region"
        aria-labelledby="hdr_now_playing"
    >
        <div class="card-header bg-primary-dark">
            <h3
                id="hdr_now_playing"
                class="card-title"
            >
                {{ $gettext('On the Air') }}
            </h3>
        </div>
        <div class="card-body">
            <p class="card-text">
                {{
                    $gettext('Information about the current playing track will appear here once your station has started.')
                }}
            </p>
        </div>
    </section>
</template>
