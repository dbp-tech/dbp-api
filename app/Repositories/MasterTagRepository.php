<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\MasterTag;
use App\Models\Tag;
use App\Models\Variant;
use Illuminate\Support\Facades\Validator;

class MasterTagRepository
{
    public function index($filters, $companyId)
    {
        $masterTag = MasterTag::with([]);
        if (!empty($filters['title'])) {
            $masterTag = $masterTag->where('title', 'LIKE', '%' . $filters['title'] . '%');
        }
        $masterTag = $masterTag->where('company_id', $companyId);
        $masterTag = $masterTag->orderBy('id', 'desc')->paginate(25);
        return $masterTag;
    }

    public function save($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'title' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code MTR-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code MTR-S: company not found');

            if (!$data['id']) {
                $masterTag = new MasterTag();
                $masterTag->company_id = $company->id;
            } else {
                $masterTag = MasterTag::find($data['id']);
                if (!$masterTag) return resultFunction('Err MTR-S: master tag not found');
            }
            $masterTag->title = $data['title'];
            $masterTag->save();

            return resultFunction("Success to create master tags", true, $masterTag);
        } catch (\Exception $e) {
            return resultFunction("Err code MTR-S catch: " . $e->getMessage());
        }
    }

    public function delete($id, $companyId) {
        try {
            $masterTag =  MasterTag::with(['tags'])->find($id);
            if (!$masterTag) return resultFunction('Err MTR-D: master tag not found');

            if ($masterTag->company_id != $companyId) return resultFunction('Err MTR-D: master tag not found');

            if (count($masterTag->tags) > 0) return resultFunction('Err MTR-D: master tag cant be deleted because it has some tags');

            $masterTag->delete();

            return resultFunction("Success to delete master tag", true);
        } catch (\Exception $e) {
            return resultFunction("Err code MTR-D catch: " . $e->getMessage());
        }
    }

    public function submitTag($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'taggable_id' => 'required',
                'taggable_type' => 'required',
                'master_tag_id' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code MTR-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code MTR-S: company not found');

            $masterTag = MasterTag::find($data['master_tag_id']);
            if (!$masterTag) return resultFunction('Err code MTR-S: master tag not found');

            $tag = new Tag();
            $tag->taggable_id = $data['taggable_id'];
            $tag->taggable_type = $data['taggable_type'];
            $tag->master_tag_id = $data['master_tag_id'];
            $tag->save();

            return resultFunction("Success to create tags", true, $tag);
        } catch (\Exception $e) {
            return resultFunction("Err code MTR-S catch: " . $e->getMessage());
        }
    }
}
