{# Admin Template View | base-app | 2.0 #}
<!DOCTYPE html>
<html lang="{{ substr(i18n.lang(), 0, 2) }}">
    <head>
        <meta charset="utf-8">
        {{ getTitle() }}
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        {{ stylesheetLink('css/bootstrap.min.css') }}
        {{ this.assets.outputCss() }}
        <!-- Fav and touch icons -->
        <link rel="shortcut icon" href="{{ url.getStatic('favicon.ico') }}">
    </head>
    <body>
        <div id="wrap">
            <header class="navbar navbar-default navbar-fixed-top">
                <div class="container">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#header-collapse"><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></button>
                        {{ linkTo([NULL, config.app.name, 'class' : 'navbar-brand']) }}
                    </div>
                    <div class="collapse navbar-collapse" id="header-collapse">
                        <ul class="nav navbar-nav">
                            <li>{{ linkTo(NULL, '<span class="glyphicon glyphicon-home"></span> ' ~ __('Home')) }}</li>
                            <li class="active">{{ linkTo('admin', '<span class="glyphicon glyphicon-wrench"></span> ' ~ __('Admin panel')) }}</li>
                        </ul>
                        {% if ! auth.logged_in() %}
                            {{ form('user/signin', 'class' : 'navbar-form pull-right') }}
                            {{ textField([ 'username', 'class' : 'form-control', 'style' : 'width: 200px', 'placeholder' : __('Username') ]) }}
                            {{ passwordField([ 'password', 'class' : 'form-control', 'style' : 'width: 200px', 'placeholder' : __('Password') ]) }}
                            {{ submitButton([ 'name' : 'submit_signin', 'class' : 'btn btn-default navbar-btn', __('Sign in') ]) }}
                            {{ endForm() }}
                        {% else %}
                        <ul class="nav navbar-nav pull-right">
                            <li class="dropdown">
                                    {{ linkTo([ '#', 'class' : 'dropdown-togle', 'data-toggle' : 'dropdown', auth.get_user().username ~ '<b class="caret"></b>' ]) }}
                                <ul class="dropdown-menu">
                                    <li class="dropdown-header">{{ auth.get_user().email }}</li>
                                    <li>{{ linkTo('user', '<span class="glyphicon glyphicon-user"></span> ' ~ __('Account')) }}</li>
                                    <li class="divider"></li>
                                    <li>{{ linkTo('user/signout', '<span class="glyphicon glyphicon-log-out"></span> ' ~ __('Sign out')) }}</li>
                                </ul>
                            </li>
                        </ul>
                        {% endif%}
                    </div>
                </div>
            </header>

            <div class="container">
                {{ content() }}
            </div>
        </div>
        <footer class="navbar navbar-inverse navbar-fixed-bottom">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#footer-collapse"><span class="sr-only">Toggle navigation</span><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></button>
                    <p class="navbar-text">
                        {{ linkTo(NULL, config.app.name) }} &copy; {{ date('Y') }}
                        <span class="text-muted"> | Phalcon {{ version() }}</span>
                    </p>
                </div>
                <div class="collapse navbar-collapse" id="footer-collapse">
                    <ul class="nav navbar-nav pull-left">
                        <li>{{ linkTo('user/signup', __('Sign up')) }}</li>
                    </ul>
                    <ul class="nav navbar-nav pull-right">
                        <li class="dropdown">
                            {{ linkTo([ '#', 'class' : 'dropdown-togle', 'data-toggle' : 'dropdown', __('Language') ~ '<b class="caret"></b>' ]) }}
                            <ul class="dropdown-menu">
                                {% for lang, language in siteLangs %}
                                <li>{{ linkTo('lang/set/' ~ lang, language) }}</li>
                                {% endfor %}
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </footer>
        {{ javascriptInclude('js/jquery.min.js') }}
        {{ javascriptInclude('js/bootstrap.min.js') }}
        <!-- Enable responsive features in IE8 -->
        <!--[if lt IE 9]>
        {{ javascriptInclude('js/respond.min.js') }}
        <![endif]-->
        {{ this.assets.outputJs() }}
        {% if count(scripts) %}
            {% for script in scripts %}
            <script type="text/javascript">{{ script }}</script>
            {% endfor %}
        {% endif %}
    </body>
</html>