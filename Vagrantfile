# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  # Box Ubuntu 20.04
  config.vm.box = "ubuntu/focal64"
  config.vm.box_version = "20240821.0.1"

  # Private network (host-only)
  config.vm.network "private_network", ip: "192.168.33.10"

  # Public network bridged, tự động chọn interface để tránh prompt
  # Lấy đúng tên interface của bạn từ danh sách Vagrant báo: 
  # 1) MediaTek Wi-Fi 6 MT7921 Wireless LAN Card
  config.vm.network "public_network", bridge: "MediaTek Wi-Fi 6 MT7921 Wireless LAN Card"

  # Synced folder
  config.vm.synced_folder "./sources", "/vagrant"

  # VirtualBox provider
  config.vm.provider "virtualbox" do |vb|
    vb.gui = true
    vb.memory = "4096"
    vb.cpus = 2
  end

  # Provision script
  config.vm.provision "shell", inline: <<-SHELL
    echo "Cập nhật hệ thống..."
    apt-get update -y
    apt-get install -y apache2 docker.io docker-compose git make net-tools sudo curl unzip

    # Thêm user vagrant vào nhóm docker
    usermod -aG docker vagrant

    # Bật Docker service
    systemctl enable docker
    systemctl start docker

    # Vào thư mục /vagrant (synced folder) để chạy docker-compose
    cd /vagrant

    echo "Khởi động Docker Compose..."
    docker-compose down
    docker-compose up -d
  SHELL

  # Boot timeout dài
  config.vm.boot_timeout = 600
end
