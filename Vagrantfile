# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|
    config.vm.box = "centos/7"

    config.vm.define :libmqtt do |lmq|
        lmq.vm.synced_folder ".", "/vagrant", disabled: true
        lmq.vm.provision :shell, path: "VagrantProvisionScripts/base.sh"
    end
end
