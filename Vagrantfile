# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|

  config.vm.box = "ubuntu/trusty64"
  config.vm.hostname = "dev.azuracast.com"

  # Support for Parallels provider for Vagrant
  # See: http://parallels.github.io/vagrant-parallels/docs/
  config.vm.provider "parallels" do |v, override|
    # v.update_guest_tools = true
    v.memory = 1024

    override.vm.box = "parallels/ubuntu-14.04"
  end

  # Customization for Virtualbox (default provider)
  config.vm.provider :virtualbox do |vb|
    vb.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
    vb.customize ["modifyvm", :id, "--memory", "1024"]
  end

  # Disabled for Windows 10 + VirtualBox
  # config.vm.network "private_network", ip: "192.168.80.80"

  # Web Server
  config.vm.network :forwarded_port, guest: 80, host: 8080

  # InfluxDB
  config.vm.network :forwarded_port, guest: 8083, host: 8083
  config.vm.network :forwarded_port, guest: 8086, host: 8086

  # IceCast
  config.vm.network :forwarded_port, guest: 8000, host: 8088
  config.vm.network :forwarded_port, guest: 8001, host: 8089

  # MySQL
  config.vm.network :forwarded_port, guest: 3306, host: 8306

  config.vm.synced_folder ".", "/var/azuracast/www", create: true, user: "azuracast", group: "www-data"
  config.vm.synced_folder ".", "/vagrant"

  config.vm.provision "shell" do |s|
    s.path = "util/vagrant_deploy.sh"
  end

end
