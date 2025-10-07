# DNS Records Configuration

## Domain: kineticev.in

| Host/Name | Type | Value/Target | TTL |
|-----------|------|-------------|-----|
| kineticev.in | A | d1ms4ceu603fqn.cloudfront.net. | - |
| kineticev.in | MX | 10 mx07.mailngx.com<br>05 mx06.mailngx.com | 300 |
| kineticev.in | NS | ns-1309.awsdns-35.org.<br>ns-100.awsdns-12.com.<br>ns-752.awsdns-30.net.<br>ns-1606.awsdns-08.co.uk. | 172800 |
| kineticev.in | SOA | ns-1309.awsdns-35.org. awsdns-hostmaster.amazon.com. 1 7200 900 1209600 86400 | 900 |
| kineticev.in | TXT | "v=spf1 a include:_spfnew.logix.in ~all" | 300 |

## SSL Validation Records

| Host/Name | Type | Value/Target | TTL |
|-----------|------|-------------|-----|
| _5d8f7443d3bb1439799e3db75de4341d.kineticev.in | CNAME | _49ab356b24cd47d1023bc0d457e4462c.xlfgrmvvlj.acm-validations.aws. | 300 |

## Email Configuration Records

| Host/Name | Type | Value/Target | TTL |
|-----------|------|-------------|-----|
| _dmarc.kineticev.in | TXT | "v=DMARC1; p=none;" | 300 |
| 4spgoe45cjr42oszxo6avjqu5d2wtuwv._domainkey.kineticev.in | CNAME | 4spgoe45cjr42oszxo6avjqu5d2wtuwv.dkim.amazonses.com | 1800 |
| iswdz6or5fc6t5zpfebkh7m36s5jcsjb._domainkey.kineticev.in | CNAME | iswdz6or5fc6t5zpfebkh7m36s5jcsjb.dkim.amazonses.com | 1800 |
| l4u4zf32zn4ftsxzfvcl4327ux7mugp2._domainkey.kineticev.in | CNAME | l4u4zf32zn4ftsxzfvcl4327ux7mugp2.dkim.amazonses.com | 1800 |
| mailngx._domainkey.kineticev.in | TXT | "v=DKIM1; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzydnRyGWaqYbLEQ3mMAxIqDuK+VRyayg6MfXaqLmKgEE4eTlQXwSLLkq04LDThnTL0N7KFj6sDHuZ9cfuLTCdWu/upbJA2vAMkZoKPKcZms4NHhCBF5Vk+tH5AfQ7DE6umIxg72tG1g2HdR7DBDidfgYDlOfDnt0xafUvyM9wjYLCZ0aliP5CdXUtatjAQIYS0lUncww4Up2xl8ZZ9TmNXjX00dhH8TBmgFe6mQeU+T+oco+JTf7MlBsCnfKm+66ksP/kjQpMqzPbT0MFe/w3DtLEnxsbYe7mdbuFHNQ7kN/jqJsCENkO1DRndXRliuiLEFDbJMRue3EN2savDCHkQIDAQAB;" | 300 |

## Subdomain Records

| Host/Name | Type | Value/Target | TTL |
|-----------|------|-------------|-----|
| blog.kineticev.in | A | 118.139.165.144 | 300 |
| info.kineticev.in | MX | 10 feedback-smtp.ap-south-1.amazonses.com | 300 |
| info.kineticev.in | TXT | "v=spf1 include:amazonses.com ~all" | 300 |
| test.kineticev.in | A | 43.204.72.252 | 300 |
| uat.kineticev.in | A | 118.139.165.144 | 300 |
| www.kineticev.in | CNAME | kineticev.in | - |

## Notes

- All TTL values are in seconds
- The domain uses AWS Route 53 name servers
- Email services are configured for both Mailngx and Amazon SES
- Main domain points to CloudFront distribution
- SSL validation is handled through AWS ACM
- DMARC policy is set to none (monitoring mode)