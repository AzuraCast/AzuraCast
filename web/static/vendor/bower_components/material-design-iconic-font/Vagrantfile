Vagrant.configure("2") do |config|
    config.vm.box = "ubuntu/trusty64"
    config.vm.provision :shell, path: "provision.sh"
    config.vm.synced_folder ".", "/vagrant", create: true
end