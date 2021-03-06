<?php

declare(strict_types=1);

namespace KhsCI\Service\Checks;

use Curl\Curl;
use Exception;
use KhsCI\Support\Date;
use KhsCI\Support\Log;

/**
 * Class Run.
 *
 * @see https://developer.github.com/v3/checks/runs/
 */
class Run
{
    protected $header = [
        'Accept' => 'application/vnd.github.antiope-preview+json',
    ];

    /**
     * @var Curl
     */
    protected $curl;

    private $api_url;

    public function __construct(Curl $curl, string $api_url)
    {
        $this->curl = $curl;

        $this->api_url = $api_url;
    }

    /**
     * @param string     $repo_full_name
     * @param string     $name           Required. The name of the check (e.g., "code-coverage").
     * @param string     $branch         Required. The name of the branch to perform a check against.
     * @param string     $commit_id      Required. The SHA of the commit.
     * @param string     $details_url    the URL of the integrator's site that has the full details of the check
     * @param string     $external_id    a reference for the run on the integrator's system
     * @param string     $status         The current status. Can be one of queued, in_progress, or completed. Default:
     *                                   queued
     * @param int        $started_at     the time that the check run began in ISO 8601 format: YYYY-MM-DDTHH:MM:SSZ
     * @param int        $completed_at   Required. The time the check completed in ISO 8601 format:
     *                                   YYYY-MM-DDTHH:MM:SSZ. Required if you provide conclusion.
     * @param string     $conclusion     Required. The final conclusion of the check. Can be one of success, failure,
     *                                   neutral,
     *                                   cancelled, timed_out, or action_required.
     * @param string     $title
     * @param string     $summary
     * @param string     $text
     * @param array|null $annotations    [$annotation, $annotation2]
     * @param array|null $images         [$image, $image2]
     * @param array|null $actions        [$action]
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function create(string $repo_full_name,
                           string $name,
                           string $branch,
                           string $commit_id,
                           string $details_url,
                           string $external_id,
                           string $status,
                           int $started_at = null,
                           int $completed_at = null,
                           string $conclusion = null,
                           string $title = null,
                           string $summary = null,
                           string $text = null,
                           array $annotations = null,
                           array $images = null,
                           array $actions = null)
    {
        $url = $this->api_url.'/repos/'.$repo_full_name.'/check-runs';

        $data = array_filter([
            'name' => $name,
            'head_branch' => $branch,
            'head_sha' => $commit_id,
            'details_url' => $details_url,
            'external_id' => $external_id,
            'status' => $status,
            'started_at' => Date::Int2ISO($started_at),
            'completed_at' => Date::Int2ISO($completed_at),
            'conclusion' => $conclusion,
            'output' => array_filter([
                'title' => $title,
                'summary' => $summary,
                'text' => $text,
                'annotations' => $annotations,
                'images' => $images,
            ]),
            'actions' => $actions,
        ]);

        $request = json_encode($data);

        $output = $this->curl->post($url, $request, $this->header);

        $http_return_code = $this->curl->getCode();

        if (201 !== $http_return_code) {
            Log::debug(__FILE__, __LINE__, 'Http Return code is not 201 '.$http_return_code);
        }

        return $output;
    }

    /**
     * @param string $filename      Required. The name of the file to add an annotation to.
     * @param string $blog_href     Required. The file's full blob URL.
     * @param int    $start_line    Required. The start line of the annotation.
     * @param int    $end_line      Required. The end line of the annotation.
     * @param string $warning_level Required. The warning level of the annotation. Can be one of notice, warning, or
     *                              failure.
     * @param string $message       Required. A short description of the feedback for these lines of code. The maximum
     *                              size is 64 KB.
     * @param string $title         The title that represents the annotation. The maximum size is 255 characters.
     * @param string $raw_details   Details about this annotation. The maximum size is 64 KB.
     *
     * @return array
     */
    public static function createAnnotation(string $filename,
                                            string $blog_href,
                                            int $start_line,
                                            int $end_line,
                                            string $warning_level,
                                            string $message,
                                            string $title = null,
                                            string $raw_details = null)
    {
        return [
            'filename' => $filename,
            'blog_href' => $blog_href,
            'start_line' => $start_line,
            'end_line' => $end_line,
            'warning_level' => $warning_level,
            'message' => $message,
            'title' => $title,
            'raw_details' => $raw_details,
        ];
    }

    /**
     * @param string $alt       Required. The alternative text for the image.
     * @param string $image_url Required. The full URL of the image.
     * @param string $caption   a short image description
     *
     * @return array
     */
    public static function createImage(string $alt,
                                       string $image_url,
                                       string $caption)
    {
        return [
            'alt' => $alt,
            'image_url' => $image_url,
            'caption' => $caption,
        ];
    }

    /**
     * @param string $label
     * @param string $identifier
     * @param string $description
     *
     * @return array
     *
     * @see https://developer.github.com/changes/2018-05-23-request-actions-on-checks/
     */
    public static function createAction(string $label = 'Fix',
                                        string $identifier = 'fix_errors',
                                        string $description = 'Allow us to fix these errors for you')
    {
        return [
            'label' => $label,
            'identifier' => $identifier,
            'description' => $description,
        ];
    }

    /**
     * @param string     $repo_full_name
     * @param string     $check_run_id
     * @param string     $name
     * @param string     $branch
     * @param string     $commit_id
     * @param string     $details_url
     * @param string     $external_id
     * @param string     $status
     * @param int        $started_at
     * @param int        $completed_at
     * @param string     $conclusion
     * @param string     $title
     * @param string     $summary
     * @param string     $text
     * @param array|null $annotations    [$annotation, $annotation2]
     * @param array|null $images         [$image, $image2]
     * @param array|null $actions        [$action]
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function update(string $repo_full_name,
                           string $check_run_id,
                           string $name,
                           string $branch,
                           string $commit_id,
                           string $details_url,
                           string $external_id,
                           string $status,
                           int $started_at = null,
                           int $completed_at = null,
                           string $conclusion = null,
                           string $title = null,
                           string $summary = null,
                           string $text = null,
                           array $annotations = null,
                           array $images = null,
                           array $actions = null)
    {
        $url = $this->api_url.'/repos/'.$repo_full_name.'/check-runs/'.$check_run_id;

        $data = array_filter([
            'name' => $name,
            'head_branch' => $branch,
            'head_sha' => $commit_id,
            'details_url' => $details_url,
            'external_id' => $external_id,
            'status' => $status,
            'started_at' => Date::Int2ISO($started_at),
            'completed_at' => Date::Int2ISO($completed_at),
            'conclusion' => $conclusion,
            'output' => array_filter([
                'title' => $title,
                'summary' => $summary,
                'text' => $text,
                'annotations' => $annotations,
                'images' => $images,
            ]),
            'actions' => $actions,
        ]);

        $request = json_encode($data);

        $output = $this->curl->patch($url, $request, $this->header);

        $http_return_header = $this->curl->getCode();

        if (200 !== $http_return_header) {
            Log::debug(__FILE__, __LINE__, 'Http Return Code is not 200 '.$http_return_header);
        }

        return $output;
    }

    /**
     * List check runs for a specific ref.
     *
     * @param string $repo_full_name
     * @param string $ref            Required. Can be a SHA, branch name, or tag name.
     * @param string $check_name     returns check runs with the specified name
     * @param string $status         Returns check runs with the specified status. Can be one of queued, in_progress, or
     *                               completed.
     * @param string $filter         Filters check runs by their completed_at timestamp. Can be one of latest (returning
     *                               the most recent check runs) or all. Default: latest
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function listSpecificRef(string $repo_full_name,
                                    string $ref,
                                    string $check_name,
                                    string $status,
                                    string $filter)
    {
        $url = $this->api_url.'/repos/'.$repo_full_name.'/commits/'.$ref.'/check-runs';

        $data = [
            'check_name' => $check_name,
            'status' => $status,
            'filter' => $filter,
        ];

        $url = $url.'?'.http_build_query($data);

        return $this->curl->get($url, null, $this->header);
    }

    /**
     * List check runs in a check suite.
     *
     * @param string $repo_full_name
     * @param int    $id
     * @param string $check_name     returns check runs with the specified name
     * @param string $status         Returns check runs with the specified status. Can be one of queued, in_progress, or
     *                               completed.
     * @param string $filter         Filters check runs by their completed_at timestamp. Can be one of latest (returning
     *                               the most recent check runs) or all. Default: latest
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function listCheckSuite(string $repo_full_name,
                                   int $id,
                                   string $check_name,
                                   string $status,
                                   string $filter)
    {
        $url = $this->api_url.'/repos/'.$repo_full_name.'/check-suites/'.$id.'/check-rus';

        $data = [
            'check_name' => $check_name,
            'status' => $status,
            'filter' => $filter,
        ];

        $url = $url.'?'.http_build_query($data);

        return $this->curl->get($url, null, $this->header);
    }

    /**
     * Get a single check run.
     *
     * @param string $repo_full_name
     * @param int    $check_run_id
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function getSingle(string $repo_full_name, int $check_run_id)
    {
        $url = $this->api_url.'/repos/'.$repo_full_name.'/check-runs/'.$check_run_id;

        return $this->curl->get($url, null, $this->header);
    }

    /**
     * List annotations for a check run.
     *
     * @param string $repo_full_name
     * @param int    $check_run_id
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function listAnnotations(string $repo_full_name, int $check_run_id)
    {
        $url = $this->api_url.'/repos/'.$repo_full_name.'/check-runs/'.$check_run_id.'/annotations';

        return $this->curl->get($url, null, $this->header);
    }
}
