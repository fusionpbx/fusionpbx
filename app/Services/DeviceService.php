<?php

namespace App\Services;

class DeviceService
{
    protected ?string $templateDir = null;

    public function getVendorByMac(string $mac): string
    {
        if (empty($mac)) {
            return '';
        }

        $mac = preg_replace('#[^a-fA-F0-9./]#', '', $mac);
        $mac = strtolower($mac);
        $prefix = substr($mac, 0, 6);

        $vendors = [
            '00085d' => 'aastra',
            '00040d' => 'avaya',
            '001b4f' => 'avaya',
            '00549f' => 'avaya',
            '048a15' => 'avaya',
            '10cdae' => 'avaya',
            '14612f' => 'avaya',
            '24b209' => 'avaya',
            '24d921' => 'avaya',
            '2cf4c5' => 'avaya',
            '3475c7' => 'avaya',
            '38bb3c' => 'avaya',
            '3c3a73' => 'avaya',
            '3cb15b' => 'avaya',
            '44322a' => 'avaya',
            '506184' => 'avaya',
            '50cd22' => 'avaya',
            '581626' => 'avaya',
            '6049c1' => 'avaya',
            '646a52' => 'avaya',
            '64a7dd' => 'avaya',
            '64c354' => 'avaya',
            '6ca849' => 'avaya',
            '6cfa58' => 'avaya',
            '703018' => 'avaya',
            '7038ee' => 'avaya',
            '7052c5' => 'avaya',
            '707c69' => 'avaya',
            '801daa' => 'avaya',
            '848371' => 'avaya',
            '90fb5b' => 'avaya',
            'a009ed' => 'avaya',
            'a01290' => 'avaya',
            'a051c6' => 'avaya',
            'a4251b' => 'avaya',
            'a47886' => 'avaya',
            'b0adaa' => 'avaya',
            'b4475e' => 'avaya',
            'b4a95a' => 'avaya',
            'b4b017' => 'avaya',
            'bcadab' => 'avaya',
            'c057bc' => 'avaya',
            'c4bed4' => 'avaya',
            'c81fea' => 'avaya',
            'c8f406' => 'avaya',
            'ccf954' => 'avaya',
            'd47856' => 'avaya',
            'd4ea0e' => 'avaya',
            'e45d52' => 'avaya',
            'f81547' => 'avaya',
            'f873a2' => 'avaya',
            'fc8399' => 'avaya',
            'fca841' => 'avaya',

            '001873' => 'cisco',
            'a44c11' => 'cisco',
            '0021a0' => 'cisco',
            '30e4db' => 'cisco',
            '002155' => 'cisco',
            '68efbd' => 'cisco',

            '000b82' => 'grandstream',

            '00177d' => 'konftel',

            '00045a' => 'linksys',
            '000625' => 'linksys',
            '000e08' => 'linksys',

            '08000f' => 'mitel',

            '0080f0' => 'panasonic',
            'bcc342' => 'panasonic',
            '080023' => 'panasonic',

            '0004f2' => 'polycom',
            '00907a' => 'polycom',
            '64167f' => 'polycom',
            '482567' => 'polycom',

            '000413' => 'snom',

            '001565' => 'yealink',
            '805ec0' => 'yealink',
            '805e0c' => 'yealink',
            'ec1da9' => 'yealink',
            '249ad8' => 'yealink',
            'c4fc22' => 'yealink',
            '44dbd2' => 'yealink',

            '00268b' => 'escene',

            '001fc1' => 'htek',

            '0c383e' => 'fanvil',

            '7c2f80' => 'gigaset',
            '14b370' => 'gigaset',
            '002104' => 'gigaset',

            '0021f2' => 'flyingvoice',
        ];

        return $vendors[$prefix] ?? '';
    }

    public function getVendorByAgent(string $agent): string
    {
        $agent = strtolower($agent);

        $matchers = [
            'aastra' => '/aastra/',
            'cisco-spa' => '/cisco\/spa/',
            'cisco' => '/cisco/',
            'digium' => '/digium/',
            'grandstream' => '/grandstream/',
            'linksys' => '/linksys/',
            'polycom' => '/polycom/',
            'yealink' => '/yealink|vp530p/',
            'snom' => '/snom/',
            'addpac' => '/addpac/',
            'escene' => '/^es\d{3}/',
            'panasonic' => '/panasonic/',
            'gigaset' => '/n510/',
            'htek' => '/htek/',
            'fanvil' => '/fanvil/',
            'flyingvoice' => '/flyingvoice/',
        ];

        foreach ($matchers as $vendor => $pattern) {
            if (preg_match($pattern, $agent)) {
                return $vendor;
            }
        }

        return '';
    }
    public function getDeviceTemplates(): array
    {
        $templateDir = $this->getTemplateDir();
        $templates = [];

        if (!is_dir($templateDir)) {
            return $templates;
        }

        $vendors = $this->getAvailableVendors($templateDir);

        foreach ($vendors as $vendor) {
            $vendorPath = $templateDir . '/' . $vendor;

            if (!is_dir($vendorPath)) {
                continue;
            }

            $vendorTemplates = [];
            $directories = scandir($vendorPath);

            if (is_array($directories)) {
                foreach ($directories as $dir) {
                    if ($dir !== '.' && $dir !== '..' && $dir[0] !== '.' && is_dir($vendorPath . '/' . $dir)) {
                        $vendorTemplates[] = [
                            'value' => $vendor . '/' . $dir,
                            'label' => $vendor . '/' . $dir,
                            'vendor' => $vendor,
                            'template' => $dir
                        ];
                    }
                }
            }

            if (!empty($vendorTemplates)) {
                $templates[$vendor] = [
                    'name' => $vendor,
                    'templates' => $vendorTemplates
                ];
            }
        }

        return $templates;
    }


    private function getAvailableVendors(string $templateDir): array
    {
        $vendors = [];

        if (!is_dir($templateDir)) {
            return $vendors;
        }

        $directories = scandir($templateDir);

        if (is_array($directories)) {
            foreach ($directories as $dir) {
                if ($dir !== '.' && $dir !== '..' && $dir[0] !== '.' && is_dir($templateDir . '/' . $dir)) {
                    $vendors[] = $dir;
                }
            }
        }

        return $vendors;
    }

    public function getTemplateInfo(string $deviceTemplate): array
    {
        if (empty($deviceTemplate)) {
            return [];
        }

        $templateParts = explode('/', $deviceTemplate);
        if (count($templateParts) < 2) {
            return [];
        }

        $vendor = $templateParts[0];
        $template = $templateParts[1];
        $templateDir = $this->getTemplateDir();

        $templatePath = $templateDir . '/' . $vendor . '/' . $template;
        $imagePath = $templatePath . '/' . $template . '.jpg';

        $info = [
            'vendor' => $vendor,
            'template' => $template,
            'path' => $templatePath,
            'exists' => is_dir($templatePath),
            'image' => null
        ];

        if (file_exists($imagePath)) {
            $info['image'] = [
                'path' => $imagePath,
                'base64' => base64_encode(file_get_contents($imagePath)),
                'exists' => true
            ];
        }

        return $info;
    }


    public function getTemplateDir(): string
    {
        if (!empty($this->templateDir)) {
            return $this->templateDir;
        }

        $os = PHP_OS;

        if ($os === 'Linux') {
            $paths = [
                '/usr/share/fusionpbx/templates/provision',
                '/etc/fusionpbx/resources/templates/provision',
            ];
        } elseif ($os === 'FreeBSD') {
            $paths = [
                '/usr/local/share/fusionpbx/templates/provision',
                '/usr/local/etc/fusionpbx/resources/templates/provision',
            ];
        } else {
            $paths = [];
        }

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $this->templateDir = $path;
                break;
            }
        }

        if (empty($this->templateDir)) {
            $this->templateDir = resource_path('templates/provision');
        }

        if (
            auth()->check() &&
            auth()->user()->domain &&
            is_dir($this->templateDir . '/' . auth()->user()->domain->domain_name)
        ) {
            $this->templateDir .= '/' . auth()->user()->domain->domain_name;
        }

        return $this->templateDir;
    }
}
