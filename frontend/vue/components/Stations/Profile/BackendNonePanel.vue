<template>
    <section
        id="profile-backend"
        class="card"
        role="region"
    >
        <div class="card-header bg-primary-dark">
            <h3 class="card-title">
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
