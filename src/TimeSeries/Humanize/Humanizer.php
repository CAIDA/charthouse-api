<?php
/**
 * This software is Copyright (c) 2013 The Regents of the University of
 * California. All Rights Reserved. Permission to copy, modify, and distribute this
 * software and its documentation for academic research and education purposes,
 * without fee, and without a written agreement is hereby granted, provided that
 * the above copyright notice, this paragraph and the following three paragraphs
 * appear in all copies. Permission to make use of this software for other than
 * academic research and education purposes may be obtained by contacting:
 *
 * Office of Innovation and Commercialization
 * 9500 Gilman Drive, Mail Code 0910
 * University of California
 * La Jolla, CA 92093-0910
 * (858) 534-5815
 * invent@ucsd.edu
 *
 * This software program and documentation are copyrighted by The Regents of the
 * University of California. The software program and documentation are supplied
 * "as is", without any accompanying services from The Regents. The Regents does
 * not warrant that the operation of the program will be uninterrupted or
 * error-free. The end-user understands that the program was developed for research
 * purposes and is advised not to rely exclusively on the program for any reason.
 *
 * IN NO EVENT SHALL THE UNIVERSITY OF CALIFORNIA BE LIABLE TO ANY PARTY FOR
 * DIRECT, INDIRECT, SPECIAL, INCIDENTAL, OR CONSEQUENTIAL DAMAGES, INCLUDING LOST
 * PROFITS, ARISING OUT OF THE USE OF THIS SOFTWARE AND ITS DOCUMENTATION, EVEN IF
 * THE UNIVERSITY OF CALIFORNIA HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE. THE UNIVERSITY OF CALIFORNIA SPECIFICALLY DISCLAIMS ANY WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 * FITNESS FOR A PARTICULAR PURPOSE. THE SOFTWARE PROVIDED HEREUNDER IS ON AN "AS
 * IS" BASIS, AND THE UNIVERSITY OF CALIFORNIA HAS NO OBLIGATIONS TO PROVIDE
 * MAINTENANCE, SUPPORT, UPDATES, ENHANCEMENTS, OR MODIFICATIONS.
 */

namespace App\TimeSeries\Humanize;


use App\Expression\PathExpression;
use App\TimeSeries\Humanize\Provider\AbstractHumanizeProvider;
use App\TimeSeries\Humanize\Provider\DefaultHumanizeProvider;
use App\TimeSeries\Humanize\Provider\DirectHumanizeProvider;
use App\TimeSeries\Humanize\Provider\GeoHumanizeProvider;
use App\TimeSeries\Humanize\Provider\InternetIdHumanizeProvider;

class Humanizer
{
    private $providers = null;

    public function __construct()
    {
        $this->providers = [
            new DirectHumanizeProvider(),
            new GeoHumanizeProvider(),
            new InternetIdHumanizeProvider(),
            new DefaultHumanizeProvider(),
        ];
    }

    /**
     * Attempt to find a human-readable name for the given nodes/finalnode
     *
     * @param string $fqid
     * @param string[] $nodes
     * @param string $finalNode
     *
     * @return string|null
     */
    public
    function humanize(string $fqid, array &$nodes, string $finalNode): ?string
    {
        if (!isset($finalNode)) {
            return null;
        }
        /** @var AbstractHumanizeProvider $provider */
        foreach ($this->providers as $provider) {
            if (($human = $provider->humanize($fqid, $nodes, $finalNode)) !== null) {
                return $human;
            }
        }
        return null; // should never happen
    }

    /**
     * Attempt to find a human-readable name for the given FQID
     *
     * @param string|array $fqid
     * @param bool $asArray
     *
     * @return string|array
     */
    public
    function humanizeFqid($fqid, bool $asArray = false)
    {
        if (!is_array($fqid)) {
            $fqid = explode(PathExpression::SEPARATOR, $fqid);
        }
        $humanNodes = [];
        $partialFqid = '';
        $partialNodes = [];
        foreach ($fqid as $node) {
            $partialFqid .= $node . PathExpression::SEPARATOR;
            $partialNodes[] = $node;
            $humanNodes[] = $this->humanize($partialFqid, $partialNodes, $node);
        }
        if ($asArray) {
            return $humanNodes;
        } else {
            return PathExpression::nodesToString($humanNodes, true);
        }
    }
}
