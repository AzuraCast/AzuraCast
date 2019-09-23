# Contributing to AzuraCast

As a free and open-source software project, we eagerly welcome contributions from the community. There are many ways to help contribute to AzuraCast's development, so you can make a difference without being a seasoned developer.

## Translating AzuraCast

Do you speak both English and another language? You can help us in a _big_ way by helping translate AzuraCast!

AzuraCast is used around the world, and we want our web application to be accessible to users who aren't familiar with English, our primary language.

Thanks to the help of our friends at CrowdIn, translating the strings used in our application is easy! Just [follow this invite link](https://crowdin.com/project/azuracast/invite) and create an account, and you can start submitting translations. CrowdIn also provides suggested translations in case you are unsure of certain words or phrases.

We do our best to incorporate translation changes as frequently as possible, but sometimes new updates are delayed. If you have completed a significant translation project, please feel free to give us a gentle reminder by creating a Github issue. Once the translations have been updated, we will close the issue to let you know.

## Testing New Platforms

Getting a project as large and complex as AzuraCast to work on many platforms is a huge effort, and is often far too complex for a single-developer project such as this.

Thankfully, we've adopted support for Docker, a tool that allows us to create prebuilt images with all of our software stack properly configured and arranged, which you can then run on just about any host that will run the latest version of Docker. It's portable, it's cross-platform, and pulling down new updates are far easier than before; for these reasons, we heavily emphasize using Docker over our older, Ubuntu-specific Ansible installation.

Although we use Docker in local development and on our testing and demonstration servers, sometimes a problem will occur that stops a particular host from working with our application. If you're a user affected by such an issue, we encourage you to advise us by creating a Github issue, especially if there is a known solution to the issue that we can apply.

## Contributing Code Changes

Despite AzuraCast's growing popularity, we almost never receive pull requests from contributors wanting to add functionality or resolve issues with our application's code. Don't let this discourage you, though; we _highly_ encourage skilled developers to contribute their expertise to our codebase whenever possible.

Due in part to the rarity of third-party contributions, we don't currently have a well-defined style guide, but when working in the application's main language (PHP), we rely heavily on the [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md) and [PSR-2](http://www.php-fig.org/psr/psr-2/) code standards established by PHP-FIG.

Accessibility, security, and modern best practices are very important in AzuraCast's development. As of this document's latest update (January 2018), we are using PHP 7.2 on all platforms, so any newly contributed code can, and should, take advantage of the full suite of new features made available in PHP 7.0 and newer.

Contributions are also welcome in the supporting technologies used to make AzuraCast possible, such as:

 - Dockerfiles (see [our separate repositories](https://github.com/AzuraCast) for Docker containers)
 - [Ansible configuration](https://github.com/AzuraCast/AzuraCast/tree/master/util/ansible) for Ansible installs and Docker installation/updates
 - Python for our auxiliary [station monitoring scripts](https://github.com/AzuraCast/station-watcher-python)
 
Instructions for developing with AzuraCast locally are [available here](https://www.azuracast.com/developers).
 
If you have questions about the guidelines above or about how to contribute to AzuraCast, please create a Github issue and we will be happy to assist you.

## The "Do Nots" of Contributing

While we appreciate everyone who is eager to contribute to this project and help it succeed, we must ask that some forms of interaction be avoided:

 - Please **do not e-mail the project developer directly** with questions or issues specific to AzuraCast, unless you were specifically requested to do so as part of an ongoing issue. Contacting me directly prevents me from tracking all outstanding issues in one place, and harms the transparency that is essential to FOSS development. If, however, you are e-mailing me to offer me a paying job...go nuts.

 - Please **do not create "this doesn't work" issues** that are just one sentence long and don't provide any insight into the scope of the issue, what changes might have triggered it, or what platform you're running on. At the very minimum, please always include what host OS you're using (i.e. Ubuntu 16.04), whether you're using the Docker or Ansible installation, and whether the problem first occurred after a recent update. For visual issues, screenshots are also greatly appreciated.
 
 - Please **refer to existing Github issues** if you are curious about the status of outstanding bug reports or new enhancement requests. Always remember that this is a volunteer project primarily built and maintained by a single developer, and manage your expectations accordingly.

## Financial contributions

We also welcome financial contributions in full transparency on our [open collective](https://opencollective.com/azuracast).
Anyone can file an expense. If the expense makes sense for the development of the community, it will be "merged" in the ledger of our open collective by the core contributors and the person who filed the expense will be reimbursed.
