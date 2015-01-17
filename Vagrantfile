# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|

  config.vm.box = "ubuntu/trusty64"
  config.vm.hostname = "dev.pvlive.me"

  if Vagrant.has_plugin?("hostsupdater")
    config.hostsupdater.aliases = ["local.ponyvillelive.com", "dev.ponyvillelive.com", "local.pvlive.me"]
  end

  config.vm.network "private_network", ip: "192.168.33.120"

  config.vm.synced_folder ".", "/var/www/vagrant"
  config.vm.synced_folder ".", "/vagrant"

  config.vm.provider :virtualbox do |vb|
    vb.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
    vb.customize ["modifyvm", :id, "--memory", "1024"]
  end

  config.vm.provision "shell" do |s|
    s.path = "util/vagrant_deploy.sh"
  end

end
