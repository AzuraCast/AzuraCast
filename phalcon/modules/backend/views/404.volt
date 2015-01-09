{# Error 404 View | base-app | 2.0 #}
<!DOCTYPE html>
<html lang="{{ substr(i18n.lang(), 0, 2) }}">
    <head>
        <meta charset="utf-8">
        <title>{{ __('Error :code', [':code' : 404]) ~ ' | ' ~ config.app.domain }}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="{{ __('Error :code', [':code' : 404]) }} - {{ __('Page not found.') }}">
        {{ stylesheetLink('css/bootstrap.min.css') }}
        {{ assets.outputCss() }}
    </head>
    <body class="text-center" style="background: #f1f1f1">
        <br />
        <h1>{{ __('Error :code', [':code' : 404]) }}</h1>
        <h1 class="text-info"><span class="glyphicon glyphicon-road"></span></h1>
        <h4 class="text-muted">{{ __('Page not found.') }}</h4>
        <hr />
        <p class="text-muted">{{ linkTo(null, config.app.name) }} &copy; {{ date('Y') }}</p>
    </body>
</html>