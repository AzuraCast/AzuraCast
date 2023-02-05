<template>
    <section
        id="profile-now-playing"
        class="card"
        role="region"
    >
        <div class="card-header bg-primary-dark">
            <h3 class="card-title">
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
