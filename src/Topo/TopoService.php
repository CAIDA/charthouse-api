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

namespace App\Topo;


class TopoService
{
    const DB_PATH_PFX = '/var/topojson';

    const DATABASES = [
        'natural-earth' => [
            'ne_10m_admin_0.continents.v3.1.0',
            'ne_10m_admin_0.countries.v3.1.0',
            'ne_10m_admin_1.regions.v3.0.0',
        ],
        'gadm' => [
            'gadm.counties.v2.0',
        ],
    ];

    /**
     * Get a list of supported databases.
     *
     * @return string[]
     */
    public function getDatabases(): array
    {
        return array_keys(TopoService::DATABASES);
    }

    /**
     * Get a list of tables for the given database.
     *
     * @var string $db
     * @return string[]
     */
    public function getTables(string $db): array
    {
        if (!array_key_exists($db, TopoService::DATABASES)) {
            throw new \InvalidArgumentException("Invalid database '$db'");
        }
        return TopoService::DATABASES[$db];
    }

    public function getTopoJson(string $db, string $table): array
    {
        if (!array_key_exists($db, TopoService::DATABASES)) {
            throw new \InvalidArgumentException("Invalid database '$db'");
        }
        if (!in_array($table, TopoService::DATABASES[$db])) {
            throw new \InvalidArgumentException("Invalid table '$table'");
        }
        // build the file path
        // TODO move this data to swift?
        $file = implode('/', [TopoService::DB_PATH_PFX, $db, $table]) . '.processed.topo.json';
        if (!file_exists($file)) {
            throw new \InvalidArgumentException("Could not load TopoJson for $db/$table ($file)");
        }
        // TODO: if this is too slow, remove envelope and return file contents
        // TODO: directly to save the json decode/encode step
        return json_decode(file_get_contents($file), true);
    }
}
