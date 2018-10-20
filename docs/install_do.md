---
title: Install with DigitalOcean
---

![](/img/DO_Logo_Horizontal_Blue.png)

Our friends over at [DigitalOcean](https://digitalocean.com) have generously sponsored AzuraCast's development, providing us with a powerful platform to launch servers quickly and easily.

Thanks to DigitalOcean's droplet provisioning tools, you can set up a new AzuraCast instance as a DO droplet with almost zero effort, without ever needing to log in to the server itself.

[[toc]]

## Installation Steps

### Create an Account

If you don't already have one, [create a new DigitalOcean account](https://m.do.co/c/21612b90440f). Setting up two-factor authentication is also highly recommended.

### Create a New Droplet

![](/img/install_do_create.png)

From your dashboard, click the "Create" dropdown at the top right, then click "Droplets".

### Droplet Settings

![](/img/install_do_distro.png)

From the image selection screen, select your preferred distribution. It's highly recommended that you select Ubuntu 18.04 for compatibility, but you can select any distribution that will run the latest version of Docker and Docker Compose.

When selecting a droplet, consider the expected number of stations and mount points you plan to operate. One single station can easily operate on a droplet with 1 VCPU and 1 or 2GB of RAM, but for multiple stations, you'll want to scale up accordingly. Keep in mind that every distinct mount point on each station increases the processing workload of the server by a small amount (usually about 10% of one VCPU).

Choose the backup, storage and datacenter region settings that are appropriate for your needs. 

Most importantly, when you reach the "Select additional options" step, make sure to check the box labeled "User data":

![](/img/install_do_userdata.png)

A text box will appear underneath the checkboxes. Copy and paste the code below (including the first line with the hash symbol) as-is into the field:

```bash
#!/bin/bash

mkdir -p /var/azuracast
cd /var/azuracast
curl -L https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/docker.sh > docker.sh
chmod a+x docker.sh
yes | ./docker.sh install
```

It is recommended to add your own SSH keys and use these to log in for enhanced security.

AzuraCast will automatically download and install after your droplet is provisioned.

### Finishing Setup

![](/img/install_do_ip.png)

As soon as the AzuraCast installer is complete, you will be able to continue setup by visiting your droplet's IP. You can find and copy your droplet's IP from the main dashboard as shown above.

### Updating AzuraCast

This process is equivalent to following the Docker installation steps (except easier!), so updating your installation is the same as it would be with any Docker installation.

The Docker Utility Script and other files can be found at `/var/azuracast/`. Check out the [Docker Utility Script](/docker_sh.html) documentation for more available commands.