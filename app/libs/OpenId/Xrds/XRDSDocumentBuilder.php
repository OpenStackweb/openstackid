<?php namespace OpenId\Xrds;
/**
 * Copyright 2016 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

/**
 * Class XRDSDocumentBuilder
 * @package OpenId\Xrds
 */
final class XRDSDocumentBuilder
{

    const ContentType   = 'application/xrds+xml';
    const Charset       = 'charset=UTF-8';
    const XRDNamespace  = 'xri://$xrd*($v*2.0)';
    const XRDSNamespace = 'xri://$xrds';

    private $elements;
    private $canonical_id;

    public function __construct($elements, $canonical_id = null)
    {
        $this->elements     = $elements;
        $this->canonical_id = $canonical_id;
    }

    public function render()
    {
        $XRDNamespace = self::XRDNamespace;
        $XRDSNamespace = self::XRDSNamespace;
        $header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<xrds:XRDS xmlns:xrds=\"{$XRDSNamespace}\" xmlns=\"{$XRDNamespace}\">\n<XRD>\n";
        $footer = "</XRD>\n</xrds:XRDS>";
        $xrds = $header;
        if (!is_null($this->canonical_id)) {
            $xrds .= "<CanonicalID>{$this->canonical_id}</CanonicalID>\n";
        }
        foreach ($this->elements as $service) {
            $xrds .= $service->render();
        }
        $xrds .= $footer;
        return $xrds;
    }
}