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

namespace App\Controller;

use App\Response\Envelope;
use App\Topo\TopoService;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class TopoController
 * @package App\Controller
 * @Route("/topo", name="topo_")
 */
class TopoController extends ApiController
{
    /**
     * List available topographic databases
     *
     * @Route("/databases/", methods={"GET"}, name="databases")
     * @SWG\Tag(name="Topographic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns a list of available topographic databases",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"topo.databases"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="array",
     *                     items=@SWG\Property(type="string")
     *                 )
     *             )
     *         }
     *     )
     * )
     * @var Request $request
     * @var SerializerInterface
     * @var TopoService $topoService
     * @return JsonResponse
     */
    public function databases(Request $request, SerializerInterface $serializer,
                              TopoService $topoService)
    {
        $env = new Envelope('topo.databases',
                            'query',
                            [],
                            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }
        $env->setData($topoService->getDatabases());
        return $this->json($env);
    }

    /**
     * Get topographic database information
     *
     * @Route("/databases/{db}/",
     *     methods={"GET"},
     *     name="database")
     * @SWG\Tag(name="Topographic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns information about the given topographic database"
     * )
     */
    /*
    public function database($db)
    {
        return $this->json([
            "database info for $db",
        ]);
    }
    */

    /**
     * List available tables for the given topographic database
     *
     * @Route("/databases/{db}/tables/",
     *     methods={"GET"},
     *     name="database_tables")
     * @SWG\Tag(name="Topographic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns a list of the available tables for the given topographic database",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"topo.tables"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="array",
     *                     items=@SWG\Property(type="string")
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     * @var string $db
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var TopoService $topoService
     * @return JsonResponse
     */
    public function tables(string $db, Request $request,
                           SerializerInterface $serializer,
                           TopoService $topoService)
    {
        $env = new Envelope('topo.tables',
                            'query',
                            [],
                            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }
        try {
            $env->setData($topoService->getTables($db));
        } catch (\InvalidArgumentException $ex) {
            $env->setError($ex->getMessage());
            return $this->json($env, 400);
        }
        return $this->json($env);
    }

    /**
     * Get TopoJSON for the given database table
     *
     * @Route("/databases/{db}/tables/{table}/",
     *     methods={"GET"},
     *     name="database_table")
     * @SWG\Tag(name="Topographic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns the TopoJSON data for the given database table",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"topo.topojson"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data"
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     * @var string $db
     * @var string $table
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var TopoService $topoService
     * @return JsonResponse
     */
    public function table(string $db, string $table, Request $request,
                          SerializerInterface $serializer,
                          TopoService $topoService)
    {
        $env = new Envelope('topo.topojson',
                            'query',
                            [],
                            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }
        try {
            $env->setData($topoService->getTopoJson($db, $table));
        } catch (\InvalidArgumentException $ex) {
            $env->setError($ex->getMessage());
            return $this->json($env, 400);
        }
        return $this->json($env);
    }
}
