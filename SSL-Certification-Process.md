# SSL Certificate Setup for Local Development

This guide outlines the process of setting up SSL certificates for local development environments using a self-signed Root CA.

## Prerequisites

1. Software Requirements:
   - OpenSSL (verify with `openssl version`)
   - XAMPP or Apache web server
   - PowerShell 5.1 or higher
   - Administrator access on Windows

2. System Requirements:
   - Windows OS
   - Sufficient disk space (minimum 1GB recommended)
   - Port 443 available for HTTPS

3. Domain Requirements:
   - Local hosts file entry for your development domain
   - Add to `C:\Windows\System32\drivers\etc\hosts`:
     ```
     127.0.0.1    dev.kineticev.in
     ```

## Certificate Storage Location

All certificates and related files will be stored in a central location. Create this structure:

```
C:/SSL_STORE/
├── ca/
│   ├── private/    # Root CA private keys
│   └── certs/      # Root CA certificates
├── domains/
│   ├── private/    # Domain private keys
│   ├── certs/      # Domain certificates
│   ├── csr/        # Certificate signing requests
│   └── config/     # Domain-specific configurations
└── apache/         # Apache configuration files
```

Create the directory structure:
```powershell
# Run in PowerShell as administrator
$SSL_STORE = "C:/SSL_STORE"
$folders = @(
    "$SSL_STORE/ca/private",
    "$SSL_STORE/ca/certs",
    "$SSL_STORE/domains/private",
    "$SSL_STORE/domains/certs",
    "$SSL_STORE/domains/csr",
    "$SSL_STORE/domains/config",
    "$SSL_STORE/apache"
)

foreach ($folder in $folders) {
    New-Item -ItemType Directory -Path $folder -Force
}

# Secure private directories
$acl = Get-Acl "$SSL_STORE/ca/private"
$acl.SetAccessRuleProtection($true, $false)
$adminRule = New-Object System.Security.AccessControl.FileSystemAccessRule("Administrators","FullControl","Allow")
$acl.AddAccessRule($adminRule)
Set-Acl "$SSL_STORE/ca/private" $acl
Set-Acl "$SSL_STORE/domains/private" $acl
```

## Table of Contents
1. [Root CA Setup](#1-root-ca-setup)
2. [Windows Trust Store Configuration](#2-windows-trust-store-configuration)
3. [Domain Certificate Creation](#3-domain-certificate-creation)
4. [Apache Configuration](#4-apache-configuration)
5. [Additional Domains](#5-additional-domains)

## 1. Root CA Setup

### Generate Root CA Private Key
```powershell
# Navigate to SSL store directory
cd C:/SSL_STORE

# Generate 4096-bit private key without password
openssl genrsa -out ca/private/ca.key 4096
```

### Generate Root CA Certificate
```powershell
# Create certificate valid for 10 years
openssl req -x509 -new -key ca/private/ca.key -sha256 -days 3650 -out ca/certs/ca.crt -config ca/config/root-ca.cnf
```

## 2. Windows Trust Store Configuration

### Import Root CA
```powershell
# Run as administrator
certutil -addstore Root certs/ca.crt

# Verify installation
certutil -store Root "Example Root CA"
```

Expected output:
```
Root "Trusted Root Certification Authorities"
================ Certificate X ================
Serial Number: [serial number]
Issuer: E=admin@example.com, CN=Root CA, OU=Development...
NotBefore: [date]
NotAfter: [date]
...
```

## 3. Domain Certificate Creation

### Create Domain Configuration Files

#### Domain Config File (dev.kineticev.in.cnf):
```ini
[req]
default_bits = 2048
prompt = no
default_md = sha256
req_extensions = req_ext
distinguished_name = dn

[dn]
C = IN
ST = Maharashtra
L = Mumbai
O = KineticEV
OU = Development
CN = dev.kineticev.in

[req_ext]
subjectAltName = @alt_names
keyUsage = digitalSignature, keyEncipherment
extendedKeyUsage = serverAuth, clientAuth

[alt_names]
DNS.1 = dev.kineticev.in
DNS.2 = *.dev.kineticev.in
```

#### Extension Config File (dev.kineticev.in.v3.ext):
```ini
[v3_ext]
authorityKeyIdentifier=keyid,issuer:always
basicConstraints=CA:FALSE
keyUsage = digitalSignature, nonRepudiation, keyEncipherment, dataEncipherment
extendedKeyUsage = serverAuth, clientAuth
subjectAltName = @alt_names

[alt_names]
DNS.1 = dev.kineticev.in
DNS.2 = *.dev.kineticev.in
```

### Generate Domain Certificate
```powershell
# Navigate to SSL store directory
cd C:/SSL_STORE

# Generate domain private key
openssl genrsa -out domains/private/dev.kineticev.in.key 2048

# Create certificate signing request (CSR)
openssl req -new `
    -key domains/private/dev.kineticev.in.key `
    -out domains/csr/dev.kineticev.in.csr `
    -config domains/config/dev.kineticev.in.cnf

# Sign certificate with Root CA
openssl x509 -req `
    -in domains/csr/dev.kineticev.in.csr `
    -CA ca/certs/ca.crt `
    -CAkey ca/private/ca.key `
    -CAcreateserial `
    -out domains/certs/dev.kineticev.in.crt `
    -days 825 `
    -sha256 `
    -extfile domains/config/dev.kineticev.in.v3.ext `
    -extensions v3_ext

# Verify certificate
openssl x509 -in certs/dev.kineticev.in.crt -text -noout
```

## 4. Apache Configuration

### Enable SSL Module
Ensure this line is uncommented in `C:/xampp/apache/conf/httpd.conf`:
```apache
LoadModule ssl_module modules/mod_ssl.so
```

### Create Virtual Host Configuration
First, create a symbolic link to the SSL store in Apache:
```powershell
# Run as administrator
cmd /c mklink /D "C:\xampp\apache\conf\ssl" "C:\SSL_STORE"
```

File: `C:/xampp/apache/conf/extra/httpd-ssl-dev.kineticev.in.conf`
```apache
# dev.kineticev.in SSL Configuration
<VirtualHost *:443>
    ServerName dev.kineticev.in
    ServerAlias *.dev.kineticev.in
    DocumentRoot "D:/K2/php"
    
    SSLEngine on
    SSLCertificateFile "C:/SSL_STORE/domains/certs/dev.kineticev.in.crt"
    SSLCertificateKeyFile "C:/SSL_STORE/domains/private/dev.kineticev.in.key"
    SSLCertificateChainFile "C:/SSL_STORE/ca/certs/ca.crt"

    <Directory "[path/to/your/php/root/folder/for/kinetic/project]">
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "C:/xampp/apache/logs/dev.kineticev.in-error.log"
    CustomLog "C:/xampp/apache/logs/dev.kineticev.in-access.log" combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName dev.kineticev.in
    ServerAlias *.dev.kineticev.in
    Redirect permanent / https://dev.kineticev.in/
</VirtualHost>
```

### Update SSL Configuration
File: `C:/xampp/apache/conf/extra/httpd-ssl.conf`
```apache
# SSL Configuration
Listen 443

# SSL Global Context
SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384
SSLHonorCipherOrder on
SSLSessionTickets off

Include conf/extra/httpd-ssl-dev.kineticev.in.conf
```

### Final Configuration Steps
1. Verify Apache Configuration:
   ```powershell
   # Test Apache configuration
   cd C:/xampp/apache/bin
   ./httpd.exe -t
   ```

2. Restart Apache:
   ```powershell
   # Stop Apache
   net stop Apache2.4
   # Start Apache
   net start Apache2.4
   ```

3. Test SSL Setup:
   ```powershell
   # Test HTTPS connection
   curl -I https://dev.kineticev.in
   
   # Verify certificate chain
   openssl s_client -connect dev.kineticev.in:443 -servername dev.kineticev.in
   ```

## 5. Additional Domains

To add more domains:
1. Repeat steps from [Domain Certificate Creation](#3-domain-certificate-creation)
2. Create new virtual host configuration file
3. Include new configuration in `httpd-ssl.conf`
4. Restart Apache

## Notes

### Security Considerations
- Keep your Root CA private key (`ca.key`) secure
- Protect the certificate store directory:
  - Only Administrators should have access to private key directories
  - Regular users should only have read access to public certificates
- Use absolute paths in all configurations to avoid path traversal issues
- Regularly audit permissions on the SSL store
- Browser Security:
  - Clear SSL state in browsers after certificate changes
  - Remove old certificates from browser trust stores
  - In Chrome, visit chrome://flags/#allow-insecure-localhost for local testing
- Development Best Practices:
  - Use different Root CA for development and production
  - Never use development certificates in production
  - Keep development certificates separate from production certificates

### Maintenance
- Back up all certificate files to a secure location
- Certificate validity periods:
  - Root CA: 10 years
  - Domain certificates: ~2.25 years (825 days)
- Document all certificate renewals and changes
- Maintain an inventory of all issued certificates

### Troubleshooting
- Always test certificates after installation
- Check Apache error logs if issues occur
- Verify certificate paths and permissions if SSL fails
- Use OpenSSL commands to validate certificates:
  ```powershell
  # Verify certificate chain
  openssl verify -CAfile C:/SSL_STORE/ca/certs/ca.crt C:/SSL_STORE/domains/certs/dev.kineticev.in.crt
  
  # Check certificate expiration
  openssl x509 -enddate -noout -in C:/SSL_STORE/domains/certs/dev.kineticev.in.crt
  ```