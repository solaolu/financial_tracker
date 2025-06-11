<?php
class Finance {
    public function suggestSavings($budget, $expenses) {
        $diff = $budget - $expenses;
        return $diff > 0 ? 'Good job! You saved $' . $diff : 'Try to cut expenses by $' . abs($diff);
    }
}
