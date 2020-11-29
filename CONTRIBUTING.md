# Contributing to AzuraCast

As a free and open-source software project, we eagerly welcome contributions from the community. There are many ways to help contribute to AzuraCast's development, so you can make a difference without being a seasoned developer.

## Contributing Code Changes

AzuraCast is open-source software, and as part of this dedication to openness and transparency, we fully support contributions from members of the community who are skilled in the languages that we use to build our applications.

A majority of our repositories come with an `.editorconfig` file in the root, which will set many standards for indentation, punctuation and other style items for you automatically. You may need to enable EditorConfig support in your IDE of choice.

If your IDE does not support EditorConfig, the most important standard to remember that we follow is the PHP Framework Interoperability Group's [PSR-12 Extended Coding Style](https://www.php-fig.org/psr/psr-12/) standard.

Accessibility, security, and modern best practices are very important in AzuraCast's development. Any newly contributed code can, and should, take advantage of the full suite of new features made available in PHP 7.4 and newer.
 
Instructions for developing with AzuraCast locally are [available here](/developers). If you have questions about the guidelines above or about how to contribute to AzuraCast, please create a Github issue, join our [Slack](https://www.azuracast.com/slack) or [Discord server.](https://www.azuracast.com/discord) Please note that support is offered on an 'as available' basis. 

Contributions are also welcome in the supporting technologies used to make AzuraCast possible, such as:

 - Dockerfiles (see [our separate repositories](https://github.com/AzuraCast) for Docker containers)
 - [Ansible configuration](https://github.com/AzuraCast/AzuraCast/tree/master/util/ansible) for Ansible installs
 


## Translating AzuraCast

Do you speak both English and another language? You can help us in a _big_ way by helping translate AzuraCast!

AzuraCast is used around the world, and we want our web application to be accessible to users who aren't familiar with English, our primary language.

Thanks to the help of our friends at CrowdIn, translating the strings used in our application is easy! Just [follow this invite link](https://crowdin.com/project/azuracast/invite) and create an account, and you can start submitting translations. CrowdIn also provides suggested translations in case you are unsure of certain words or phrases.

We do our best to incorporate translation changes as frequently as possible, but sometimes new updates are delayed. If you have completed a significant translation project, please feel free to give us a gentle reminder by creating a Github issue. Once the translations have been updated, we will close the issue to let you know.

## Testing New Platforms

Getting a project as large and complex as AzuraCast to work on many platforms is a huge effort, and is often far too complex for a single-developer project such as this.

Thankfully, we've adopted support for Docker, a tool that allows us to create prebuilt images with all of our software stack properly configured and arranged, which you can then run on just about any host that will run the latest version of Docker. It's portable, it's cross-platform, and pulling down new updates are far easier than before; for these reasons, we heavily emphasize using Docker over our older, Ubuntu-specific Ansible installation.

Although we use Docker in local development and on our testing and demonstration servers, sometimes a problem will occur that stops a particular host from working with our application. If you're a user affected by such an issue, we encourage you to advise us by creating a Github issue, especially if there is a known solution to the issue that we can apply.

## The "Do Nots" of Contributing

While we appreciate everyone who is eager to contribute to this project and help it succeed, we must ask that some forms of interaction be avoided:

 - Please **do not e-mail the project developer directly** with questions or issues specific to AzuraCast, unless you were specifically requested to do so as part of an ongoing issue. Contacting me directly prevents me from tracking all outstanding issues in one place, and harms the transparency that is essential to FOSS development. If, however, you are e-mailing me to offer me a paying job...go nuts.

 - Please **do not create "this doesn't work" issues** that are just one sentence long and don't provide any insight into the scope of the issue, what changes might have triggered it, or what platform you're running on. At the very minimum, please fill out the template provided, as it gives us a lot of information to debug and provide you with support.
 
 - Please **refer to existing Github issues** if you are curious about the status of outstanding bug reports or new enhancement requests. Always remember that this is a volunteer project primarily built and maintained by a single developer, and manage your expectations accordingly.

## Support AzuraCast

### Support us on GitHub Sponsors

Donating through GitHub Sponsors is the best way to show your support for AzuraCast's development. You can change your pledge at any time through the Sponsors Page. GitHub will match every sponsor on our page so every donation counts. 

[GitHub Sponsor Page][sponsor]


[sponsor]: https://github.com/sponsors/SlvrEagle23

### Donate to our Patreon

Donating to AzuraCast's Patreon is a great way to show your ongoing support for the project's continual development and updates. You can edit your pledge or cancel at any time.

<a href="https://www.patreon.com/bePatron?u=232463" target="_blank" title="Become a Patron"><img src="https://c5.patreon.com/external/logo/become_a_patron_button.png"></a>

### Donate via Ko-Fi

If you'd like to make a one-time donation through PayPal, you can do so by buying our lead developer a coffee (or two!) via the Ko-Fi payment service. Every donation is greatly appreciated.

<a href="https://ko-fi.com/A736ATQ" target="_blank" title="Buy me a coffee!"><img height='32' style='border:0px;height:32px;' src='https://az743702.vo.msecnd.net/cdn/kofi1.png?v=b' border='0' alt='Buy Me a Coffee at ko-fi.com' /></a>
