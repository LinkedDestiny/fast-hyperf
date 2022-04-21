<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Framework;

use Hyperf\Database\Model\Builder;
use LinkCloud\Fast\Hyperf\Framework\Entity\Page;

class BaseDao
{
    public function output(Builder $query, Page $page): array
    {
        $output = [];
        if ($page->total) {
            $output['total'] = $query->count();
        }

        if (!$page->all) {
            $query->forPage($page->page, $page->pageSize);
            $output['page'] = $page->page;
            $output['page_size'] = $page->pageSize;
        }

        $output['list'] = $query->get()->toArray();
        return $output;
    }
}
