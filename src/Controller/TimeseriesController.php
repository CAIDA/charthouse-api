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

use App\Expression\ExpressionFactory;
use App\Expression\ParsingException;
use App\Expression\PathExpression;
use App\Response\Envelope;
use App\Response\RequestParameter;
use App\TimeSeries\Backend\BackendException;
use App\TimeSeries\Backend\GraphiteBackend;
use App\TimeSeries\Humanize\Humanizer;
use App\Utils\QueryTime;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class TimeseriesController
 * @package App\Controller\Timeseries
 * @Route("/ts", name="ts_")
 */
class TimeseriesController extends ApiController
{
    /**
     * Perform a query for time series data
     *
     * TODO: figure out how to correctly add "required" to properties
     *
     * @Route("/query/", methods={"POST"}, name="query")
     * @SWG\Tag(name="Time Series")
     * @SWG\Parameter(
     *     name="query",
     *     in="body",
     *     type="object",
     *     description="Query object. Due to limitations in the current API documentation, the full expression schema cannot be properly described. See the various `*Expression` model definitions for more information about types of supported expressions.",
     *     required=true,
     *     @SWG\Schema(
     *         @SWG\Property(
     *             property="expression",
     *             type="object",
     *             description="JSON-encoded expression object. To query for multiple expressions, use the `expressions` parameter instead.",
     *             ref=@Model(type=\App\Expression\AbstractExpression::class, groups={"public"}),
     *        ),
     *        @SWG\Property(
     *             property="expressions",
     *             type="array",
     *             description="Array of JSON-encoded expression objects.",
     *             items=@SWG\Schema(ref=@Model(type=\App\Expression\AbstractExpression::class, groups={"public"})),
     *        ),
     *        @SWG\Property(
     *             property="from",
     *             type="string",
     *             description="Start time of the query (inclusive). Times can be either absolute (e.g., '2018-08-31T16:08:18Z') or relative (e.g. '-24h')",
     *             default="-7d"
     *        ),
     *        @SWG\Property(
     *             property="until",
     *             type="string",
     *             description="End time of the query (exclusive). Times can be either absolute (e.g., '2018-08-31T16:08:18Z') or relative (e.g. '-24h')",
     *             default="now"
     *        ),
     *        @SWG\Property(
     *             property="aggregation_func",
     *             type="string",
     *             default="avg",
     *             enum={"avg", "sum"},
     *             description="Aggregation function to use when down-sampling data points",
     *        ),
     *        @SWG\Property(
     *             property="annotate",
     *             type="boolean",
     *             description="Annotate time series with metadata (e.g., geographic information)",
     *             default=false
     *        ),
     *        @SWG\Property(
     *             property="adaptive_downsampling",
     *             type="boolean",
     *             description="Request that time series be down-sampled. Helps reduce query latency and result size.",
     *             default=true
     *        )
     *     ),
     *     @SWG\Schema(ref=@Model(type=\App\Expression\PathExpression::class, groups={"public"})),
     *     @SWG\Schema(ref=@Model(type=\App\Expression\ConstantExpression::class, groups={"public"})),
     *     @SWG\Schema(ref=@Model(type=\App\Expression\FunctionExpression::class, groups={"public"}))
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Contains an array of time series that matched the given query.",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"ts.query"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="array",
     *                     items=@SWG\Schema(ref=@Model(type=\App\TimeSeries\TimeSeries::class, groups={"public"}))
     *                 )
     *             )
     *         }
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Indicates that the query failed.",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"ts.query"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={"Backend failure: foo"}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="string",
     *                     enum={}
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var ExpressionFactory $expressionFactory
     * @var GraphiteBackend $tsBackend
     *
     * @return JsonResponse
     */
    public function query(Request $request, SerializerInterface $serializer,
                          ExpressionFactory $expressionFactory,
                          GraphiteBackend $tsBackend)
    {
        $env = new Envelope('ts.query',
                            'body',
                            [
                                new RequestParameter('expression', RequestParameter::ARRAY, null, false),
                                new RequestParameter('expressions', RequestParameter::ARRAY, null, false),
                                new RequestParameter('from', RequestParameter::DATETIME, new QueryTime('-24h'), false),
                                new RequestParameter('until', RequestParameter::DATETIME, new QueryTime('now'), false),
                                new RequestParameter('aggregation_func', RequestParameter::STRING, 'avg', false),
                                new RequestParameter('annotate', RequestParameter::BOOL, false, false),
                                new RequestParameter('adaptive_downsampling', RequestParameter::BOOL, true, false),
                            ],
                            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }

        // TODO: adaptive_downsampling should be protected by authorization role?

        // we need either expression or expressions
        $oneExp = $env->getParam('expression');
        $manyExps = $env->getParam('expressions');

        if ($oneExp && $manyExps) {
            $env->setError("Only one of 'expression' or 'expressions' parameters can be set");
            return $this->json($env, 400);
        }

        if ($oneExp) {
            $rawExps = [$oneExp];
        } elseif ($manyExps) {
            $rawExps = $manyExps;
        } else {
            $env->setError("Either 'expression' or 'expressions' parameters must be set");
            return $this->json($env, 400);
        }
        $exps = [];
        try {
            foreach ($rawExps as $exp) {
                $exps[] = $expressionFactory->createFromJson($exp);
            }
        } catch (ParsingException $ex) {
            $env->setError($ex->getMessage());
            return $this->json($env, 400);
        }
        // ask the time series backend to perform the query
        try {
            $tss = $tsBackend->tsQuery(
                $exps,
                $env->getParam('from'),
                $env->getParam('until'),
                $env->getParam('aggregation_func'),
                $env->getParam('annotate'),
                $env->getParam('adaptive_downsampling')
            );
            $env->setData($tss);
        } catch (BackendException $ex) {
            $env->setError($ex->getMessage());
            // TODO: check HTTP error codes used
            return $this->json($env, 400);
        }
        return $this->json($env);
    }

    /**
     * List available time series keys matching the given path expression
     *
     * @Route("/list/", methods={"GET"}, name="list")
     * @SWG\Tag(name="Time Series")
     * @SWG\Parameter(
     *     name="path",
     *     in="query",
     *     type="string",
     *     description="Path expression string",
     *     required=false,
     *     default="*",
     * )
     * @SWG\Parameter(
     *     name="absolute_paths",
     *     in="query",
     *     type="boolean",
     *     description="Return absolute paths (rather than relative)",
     *     required=false,
     *     default=false
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Indicates the list query succeeded.",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"ts.list"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="array",
     *                     description="Array of time series paths that match the query",
     *                     items=@SWG\Schema(ref=@Model(type=\App\Expression\PathExpression::class, groups={"public", "list"}))
     *                 )
     *             )
     *         }
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Indicates that the given expression could not be parsed.",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"ts.list"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="string",
     *                     enum={}
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     *
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var GraphiteBackend $tsBackend
     * @var Humanizer $humanizer
     *
     * @return JsonResponse
     */
    public function list(Request $request, SerializerInterface $serializer,
                         GraphiteBackend $tsBackend, Humanizer $humanizer)
    {
        $env = new Envelope('ts.list',
                            'query',
                            [
                                new RequestParameter('path', RequestParameter::STRING, '*', false),
                                new RequestParameter('absolute_paths', RequestParameter::BOOL, false),
                            ],
                            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }

        // parse the given path expression
        $pathExp = new PathExpression($humanizer, $env->getParam('path'));
        // ask the time series backend to find us a list of paths
        try {
            $paths = $tsBackend->pathListQuery($pathExp,
                                               $env->getParam('absolute_paths'));
            $env->setData($paths);
        } catch (BackendException $ex) {
            $env->setError($ex->getMessage());
            // TODO: check HTTP error codes used
            return $this->json($env, 400);
        } catch (ParsingException $ex) {
            $env->setError($ex->getMessage());
            // TODO: check HTTP error codes used
            return $this->json($env, 400);
        }
        return $this->json($env, 200, [], ['groups' => ['public', 'list']]);
    }
}
