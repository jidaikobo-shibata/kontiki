<?php

namespace Jidaikobo\Kontiki\Models\Traits;

trait TaxonomyTrait
{
    public function getTerms(string $taxonomy): array
    {
        $result = $this->db->table('terms as t')
            ->join('term_taxonomy as tt', 't.term_id', '=', 'tt.term_id')
            ->select('t.*')
            ->distinct()
            ->where('tt.taxonomy', $taxonomy)
            ->get()
            ->toArray();

        return $result;
    }
}
