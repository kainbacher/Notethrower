<?php

function paginator_get_start_and_end_item_for_page($itemCount, $itemsPerPage, $pageNum) {
    // internally, page numbers range from 0 to n - 1, externally from 1 to n
    $pageNum--;
    if ($pageNum < 0) $pageNum = 0;

    // determine which items to show (from - to), depending on the current page number
    $startItem = 0;
    $endItem   = 0;
    $pageCount = 0;
    if ($itemCount > $itemsPerPage) {
        $pageCount = ceil($itemCount / $itemsPerPage);
        if (!isset($pageNum)) $pageNum = 0;
        if ($pageNum > $pageCount - 1) $pageNum = $pageCount - 1;
        $startItem = $itemsPerPage * $pageNum;
        $endItem = $itemsPerPage * ($pageNum + 1) - 1;
        if ($endItem > $itemCount - 1) $endItem = $itemCount - 1;

    } else {
        //if (isset($pageNum)) unset($pageNum);
        $pageNum   = 0;
        $pageCount = 1;
        $startItem = 0;
        $endItem   = $itemCount - 1;
    }

    return array(
        'startItem' => $startItem,
        'endItem'   => $endItem,
        'pageNum'   => $pageNum,
        'pageCount' => $pageCount
    );
}

function paginator_show_navigation($urlPrefix, $itemCount, $itemsPerPage, $maxNavigationPages, $pageNum, $navPrefixStr,
        $previousPageStr, $nextPageStr, $toPagePrefixStr) {

    echo paginator_get_navigation_html($urlPrefix, $itemCount, $itemsPerPage, $maxNavigationPages, $pageNum, $navPrefixStr,
            $previousPageStr, $nextPageStr, $toPagePrefixStr);
}

function paginator_get_navigation_html($urlPrefix, $itemCount, $itemsPerPage, $maxNavigationPages,
        $pageNum, $navPrefixStr = 'Page:', $previousPageStr = 'Previous page', $nextPageStr = 'Next page',
        $toPagePrefixStr = 'Page') {

    $paramDelimiter = '?';
    if (strpos($urlPrefix, '?') !== false) $paramDelimiter = '&';

    $i_data = paginator_get_start_and_end_item_for_page($itemCount, $itemsPerPage, $pageNum);
    $startItem = $i_data['startItem'];
    $endItem   = $i_data['endItem'];
    $pageNum   = $i_data['pageNum'];
    $pageCount = $i_data['pageCount'];

    $html = '';

    // create page numbers navigation
    if ($pageCount > 1) {
        $html .= $navPrefixStr;

        if ($pageNum > 0) {
            $html .= '<a href="' . $urlPrefix . $paramDelimiter . 'page=' . $pageNum . '" title="' . $previousPageStr . '">';
            $html .= '&nbsp;&laquo;&nbsp;';
            $html .= '</a>';
        }

        for ($iS = $pageNum - $maxNavigationPages; $iS <= $pageNum + $maxNavigationPages; $iS++) {
            if ($iS >= 0 && $iS < $pageCount) {
                if ($iS == $pageNum) {
                    $html .= '<b>&nbsp;' . ($iS + 1) . '&nbsp;</b>';

                } else {
                    $html .= '<a href="' . $urlPrefix . $paramDelimiter . 'page=' . ($iS + 1) . '" title="' . $toPagePrefixStr . ' ' . ($iS + 1) . '">';
                    $html .= '&nbsp;';
                    $html .= '' . ($iS + 1);
                    $html .= '&nbsp;';
                    $html .= '</a>';
                }
            }
        }

        if ($pageNum < $pageCount - 1) {
            $html .= '<a href="' . $urlPrefix . $paramDelimiter . 'page=' . ($pageNum + 1 + 1) . '" title="' . $nextPageStr . '">';
            $html .= '&nbsp;&raquo&nbsp;';
            $html .= '</a>';
        }

        $html .= '<br><br>';
    }

    return $html;
}

?>