<?php

// src/Service/PaginationGenerator

namespace App\Service;

class PaginationGenerator {

    private $_groupSize = 10;

    /**
     * Creation de la pagination
     *
     * @param int $count => nombre d'élément devant être paginer; $divider => nombre d'élément souhaiter par page;
     *      $page => page courante
     * @param string $url => href des liens de pagination
     *
     * @return string La structure html de la pagination
     */
    public function getPagination(int $count, int $divider, ?int $page = 1, ?string $url = null) : string {

        $currentGroup = ceil($page / $this->_groupSize);

        $html = '<nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                    <li class="page-item">
                        <a id="prev" data-loop="' . ($currentGroup - 1) . '" class="page-link" href="#" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                        <span class="sr-only">Previous</span>
                        </a>
                    </li>';                 
                    
        for($i = 0, $y = 1, $g = 1, $loop = ceil($count / $divider); $i < $loop; ++$i, ++$y, $g = ceil($y / $this->_groupSize)):

            $html .= '<li class="page-item';
            if($page == $y) $html .= ' active';
            $html .= ' li-pagination-' . $g;
            if($g != $currentGroup) $html .= ' hidden';
            $html .= '">';

            $html .= '<a class="page-link" href="';
            $html .= (!empty($url)) ? $url . $y : '#';
            $html .= '">' . $y . '</a></li>';
        endfor;

        $html .= '<li class="page-item">
                    <a id="next" data-max="' . $g . '" data-loop="' . $currentGroup . '" class="page-link" href="#" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                    <span class="sr-only">Next</span>
                    </a>
                </li>
                </ul>
            </nav>';

        return $html;
    }
}