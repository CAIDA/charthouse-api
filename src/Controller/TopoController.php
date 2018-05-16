<?php

namespace App\Controller;

use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class TopoController
 * @package App\Controller
 * @Route("/topo")
 */
class TopoController extends Controller
{
    /**
     * @Route("/databases/",
     *     methods={"GET"},
     *     name="topo_databases")
     * @SWG\Tag(name="Topographic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns a list of available topographic databases"
     * )
     */
    public function databases()
    {
        return $this->json([
            'topo database list',
        ]);
    }

    /**
     * @Route("/databases/{db}/",
     *     methods={"GET"},
     *     name="topo_database")
     * @SWG\Tag(name="Topographic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns information about the given topographic database"
     * )
     */
    public function database($db)
    {
        return $this->json([
            "database info for $db",
        ]);
    }

    /**
     * @Route("/databases/{db}/tables/",
     *     methods={"GET"},
     *     name="topo_database_tables")
     * @SWG\Tag(name="Topographic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns a list of the available tables for the given topographic database"
     * )
     */
    public function tables($db)
    {
        return $this->json([
            "topo table list for $db",
        ]);
    }

    /**
     * @Route("/databases/{db}/tables/{table}/",
     *     methods={"GET"},
     *     name="topo_database_table")
     * @SWG\Tag(name="Topographic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns the TopoJSON data for the given database table"
     * )
     */
    public function table($db, $table)
    {
        return $this->json([
            "topo database table $db/$table",
        ]);
    }
}
