# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|
    config.vm.box = "centos/7"

    config.vm.network "private_network", ip: "192.168.33.11"

    config.vm.synced_folder ".", "/vagrant", disabled: true
    config.vm.synced_folder ".", "/home/vagrant/sync", disabled: true

    config.vm.synced_folder ".", "/vagrant", type: "virtualbox", mount_options: ["dmode=777", "fmode=666"]
    config.vm.synced_folder "vendor/", "/vagrant/vendor/", mount_options: ["dmode=755", "fmode=644"]

    config.vm.provision :shell, path: "VagrantProvisionScripts/base.sh"
    config.vm.boot_timeout = 500
end
