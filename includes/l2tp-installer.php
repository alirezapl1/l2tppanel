<?php
require_once 'config/database.php';

class L2TPInstaller {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    // نصب L2TP روی سرور اوبونتو
    public function installOnServer($serverId) {
        // دریافت اطلاعات سرور
        $stmt = $this->pdo->prepare("SELECT * FROM servers WHERE id = ?");
        $stmt->execute([$serverId]);
        $server = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$server) {
            return ['success' => false, 'message' => 'سرور یافت نشد'];
        }
        
        // به روزرسانی وضعیت سرور به در حال نصب
        $this->pdo->prepare("UPDATE servers SET status = 'installing' WHERE id = ?")
            ->execute([$serverId]);
        
        try {
            // اتصال SSH به سرور
            $connection = ssh2_connect($server['ip_address'], 22);
            if (!ssh2_auth_password($connection, $server['ssh_username'], $server['ssh_password'])) {
                throw new Exception('خطا در احراز هویت SSH');
            }
            
            // اجرای دستورات نصب
            $commands = [
                'apt-get update',
                'DEBIAN_FRONTEND=noninteractive apt-get install -y openswan xl2tpd ppp',
                'echo "net.ipv4.ip_forward = 1" >> /etc/sysctl.conf',
                'sysctl -p',
                
                // پیکربندی IPSec
                'cat > /etc/ipsec.conf << EOL
version 2.0
config setup
    nat_traversal=yes
    virtual_private=%v4:10.0.0.0/8,%v4:192.168.0.0/16,%v4:172.16.0.0/12
    oe=off
    protostack=netkey

conn L2TP-PSK-NAT
    rightsubnet=vhost:%priv
    also=L2TP-PSK-noNAT

conn L2TP-PSK-noNAT
    authby=secret
    pfs=no
    auto=add
    keyingtries=3
    rekey=no
    ikelifetime=8h
    keylife=1h
    type=transport
    left=' . $server['ip_address'] . '
    leftprotoport=17/1701
    right=%any
    rightprotoport=17/%any
EOL',
                
                // تنظیم کلید پیش‌فرض
                'echo "' . $server['ip_address'] . ' %any: PSK \"vpn123456\"' > /etc/ipsec.secret',
                
                // پیکربندی L2TP
                'cat > /etc/xl2tpd/xl2tpd.conf << EOL
[global]
ipsec saref = yes
saref refinfo = 30

[lns default]
ip range = 10.1.0.2-10.1.0.254
local ip = 10.1.0.1
require chap = yes
refuse pap = yes
require authentication = yes
name = l2tpd
pppoptfile = /etc/ppp/options.xl2tpd
length bit = yes
EOL',
                
                // پیکربندی PPP
                'cat > /etc/ppp/options.xl2tpd << EOL
ipcp-accept-local
ipcp-accept-remote
ms-dns 8.8.8.8
ms-dns 8.8.4.4
noccp
auth
crtscts
idle 1800
mtu 1410
mru 1410
nodefaultroute
debug
lock
proxyarp
connect-delay 5000
EOL',
                
                // راه‌اندازی سرویس‌ها
                'systemctl enable ipsec xl2tpd',
                'systemctl restart ipsec xl2tpd',
                
                // تنظیم فایروال
                'ufw allow 500/udp',
                'ufw allow 4500/udp',
                'ufw allow 1701/udp'
            ];
            
            foreach ($commands as $command) {
                $stream = ssh2_exec($connection, $command);
                stream_set_blocking($stream, true);
                $output = stream_get_contents($stream);
                
                if (strpos($output, 'error') !== false || strpos($output, 'Error') !== false) {
                    throw new Exception("خطا در اجرای دستور: " . $command . "\nخروجی: " . $output);
                }
            }
            
            // به روزرسانی وضعیت سرور به فعال
            $this->pdo->prepare("UPDATE servers SET status = 'active' WHERE id = ?")
                ->execute([$serverId]);
            
            return ['success' => true, 'message' => 'L2TP با موفقیت روی سرور نصب شد'];
            
        } catch (Exception $e) {
            // به روزرسانی وضعیت سرور به خطا
            $this->pdo->prepare("UPDATE servers SET status = 'inactive' WHERE id = ?")
                ->execute([$serverId]);
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // ایجاد کاربر جدید روی سرور
    public function createUser($serverId, $username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM servers WHERE id = ? AND status = 'active'");
        $stmt->execute([$serverId]);
        $server = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$server) {
            return ['success' => false, 'message' => 'سرور فعال یافت نشد'];
        }
        
        try {
            $connection = ssh2_connect($server['ip_address'], 22);
            if (!ssh2_auth_password($connection, $server['ssh_username'], $server['ssh_password'])) {
                throw new Exception('خطا در احراز هویت SSH');
            }
            
            // افزودن کاربر به فایل chap-secrets
            $userConfig = "\"$username\" l2tpd \"$password\" *";
            $command = "echo '$userConfig' >> /etc/ppp/chap-secrets";
            
            $stream = ssh2_exec($connection, $command);
            stream_set_blocking($stream, true);
            $output = stream_get_contents($stream);
            
            // راه‌اندازی مجدد سرویس
            ssh2_exec($connection, 'systemctl restart xl2tpd');
            
            return ['success' => true, 'message' => 'کاربر با موفقیت ایجاد شد'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>
