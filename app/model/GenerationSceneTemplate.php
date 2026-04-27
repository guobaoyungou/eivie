<?php
/**
 * 场景模板模型
 * 由生成记录一键转化而来的可复用模板
 */
namespace app\model;

use think\Model;
use think\facade\Db;

class GenerationSceneTemplate extends Model
{
    protected $name = 'generation_scene_template';
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // JSON字段自动转换
    protected $json = ['default_params', 'param_schema'];
    protected $jsonAssoc = true;
    
    /**
     * 生成类型常量
     */
    const TYPE_PHOTO = 1;  // 照片生成
    const TYPE_VIDEO = 2;  // 视频生成
    
    /**
     * 状态常量
     */
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_ENABLED = 1;   // 启用
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        return $data['status'] == self::STATUS_ENABLED ? '启用' : '禁用';
    }
    
    /**
     * 获取生成类型文本
     */
    public function getGenerationTypeTextAttr($value, $data)
    {
        $typeMap = [
            self::TYPE_PHOTO => '照片生成',
            self::TYPE_VIDEO => '视频生成'
        ];
        return $typeMap[$data['generation_type']] ?? '未知';
    }
    
    /**
     * 关联模型信息
     */
    public function modelInfo()
    {
        return $this->belongsTo('\\think\\Model', 'model_id', 'id')
            ->setTable('model_info');
    }
    
    /**
     * 关联源生成记录
     */
    public function sourceRecord()
    {
        return $this->belongsTo(GenerationRecord::class, 'source_record_id', 'id');
    }
    
    /**
     * 获取模板列表（带模型信息和分类名称）
     */
    public static function getListWithModel($where, $page = 1, $limit = 20, $order = 'sort asc, id desc')
    {
        $query = self::alias('t')
            ->leftJoin('model_info m', 't.model_id = m.id')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->field('t.*, m.model_name, m.model_code, p.provider_name')
            ->where($where);
        
        $count = $query->count();
        
        $data = self::alias('t')
            ->leftJoin('model_info m', 't.model_id = m.id')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->field('t.*, m.model_name, m.model_code, p.provider_name')
            ->where($where)
            ->page($page, $limit)
            ->order($order)
            ->select()->toArray();
        
        foreach ($data as &$item) {
            // create_time/update_time 可能已被 Model::toArray() 自动格式化为字符串，需兼容处理
            $item['create_time_text'] = $item['create_time']
                ? (is_numeric($item['create_time']) ? date('Y-m-d H:i:s', $item['create_time']) : $item['create_time'])
                : '';
            $item['update_time_text'] = $item['update_time']
                ? (is_numeric($item['update_time']) ? date('Y-m-d H:i:s', $item['update_time']) : $item['update_time'])
                : '';
            // use_count 已作为字段存储在表中，无需再动态查询
            // 检查是否需要转存（封面为第三方URL）
            $item['needs_transfer'] = \app\service\GenerationService::isThirdPartyUrl($item['cover_image'] ?? '');
            // 解析多分类名称
            $item['category_names'] = self::resolveCategoryNames($item['category_ids'] ?? '');
            // 解析多分组名称
            $item['group_names'] = self::resolveGroupNames($item['group_ids'] ?? '');
        }
        
        return ['count' => $count, 'data' => $data];
    }
    
    /**
     * 从生成记录创建场景模板
     * @param GenerationRecord $record 源生成记录
     * @param array $templateData 模板数据，支持以下字段：
     *   - template_name: 模板名称（必填）
     *   - template_code: 模板标识（可选，自动生成）
     *   - category: 分类标签
     *   - cover_image: 封面图URL
     *   - description: 模板描述
     *   - prompt: 提示词（将合并到default_params中）
     *   - default_params: 默认参数（可选，默认使用记录的input_params）
     *   - is_public: 是否公开
     */
    public static function createFromRecord(GenerationRecord $record, $templateData)
    {
        // 获取生成输出作为封面图（仅作为最终兆底）
        $output = GenerationOutput::where('record_id', $record->id)
            ->order('sort asc')
            ->find();
        
        $dbCoverImage = '';
        if ($output) {
            $dbCoverImage = $output['output_url'];
        }
        
        // 确定最终封面图URL：优先使用转存后的templateData中的cover_image
        // 其次使用数据库中的输出成品URL
        $coverImage = !empty($templateData['cover_image']) ? $templateData['cover_image'] : $dbCoverImage;
        
        // 构建 default_params：以记录原始 input_params 为基础
        $defaultParams = $record->input_params;
        if (is_string($defaultParams)) {
            $defaultParams = json_decode($defaultParams, true) ?: [];
        }
        if (!is_array($defaultParams)) {
            $defaultParams = [];
        }
        
        // 如果用户提交了 default_params，以此为准
        if (isset($templateData['default_params']) && !empty($templateData['default_params'])) {
            $defaultParams = is_array($templateData['default_params']) 
                ? $templateData['default_params'] 
                : (json_decode($templateData['default_params'], true) ?: $defaultParams);
        }
        
        // 如果用户在表单中编辑了 prompt，用新值覆盖 default_params 中的 prompt
        if (isset($templateData['prompt'])) {
            $defaultParams['prompt'] = $templateData['prompt'];
        }
        
        $template = new self();
        $template->aid = $record->aid;
        $template->bid = $record->bid;
        $template->generation_type = $record->generation_type;
        $template->source_record_id = $record->id;
        $template->template_name = $templateData['template_name'];
        $template->template_code = $templateData['template_code'] ?? self::generateCode();
        $template->category = $templateData['category'] ?? '';
        $template->category_ids = $templateData['category_ids'] ?? '';
        $template->mdid = intval($templateData['mdid'] ?? 0);
        $template->cover_image = $coverImage;
        $template->description = $templateData['description'] ?? '';
        $template->model_id = $record->model_id;
        $template->default_params = $defaultParams;
        $template->param_schema = $templateData['param_schema'] ?? null;
        $template->is_public = $templateData['is_public'] ?? 0;
        $template->status = self::STATUS_ENABLED;
        $template->sort = $templateData['sort'] ?? 0;
        
        // 新增：use_count 初始化为0（任务转模板时）
        $template->use_count = 0;
        
        // 新增：output_quantity 自动填充
        // 视频生成默认5秒，照片生成取源任务输出数量
        if ($record->generation_type == self::TYPE_VIDEO) {
            // 视频：默认5秒，如果源记录有duration参数则使用
            $inputParams = $record->input_params;
            if (is_string($inputParams)) {
                $inputParams = json_decode($inputParams, true) ?: [];
            }
            $template->output_quantity = intval($inputParams['duration'] ?? 5);
            if ($template->output_quantity < 1) {
                $template->output_quantity = 5;
            }
        } else {
            // 照片：取源任务输出数量
            $outputCount = \app\model\GenerationOutput::where('record_id', $record->id)->count();
            $template->output_quantity = $outputCount > 0 ? $outputCount : 1;
        }
        
        $template->save();
        
        return $template;
    }
    
    /**
     * 生成模板标识
     */
    public static function generateCode()
    {
        return 'TPL_' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 12));
    }
    
    /**
     * 获取可用模板列表（用于下拉选择）
     */
    public static function getAvailableList($aid, $bid, $generationType)
    {
        return self::where([
            ['aid', '=', $aid],
            ['bid', '=', $bid],
            ['generation_type', '=', $generationType],
            ['status', '=', self::STATUS_ENABLED]
        ])
        ->order('sort asc, id desc')
        ->column('id, template_name, cover_image, model_id', 'id');
    }
    
    /**
     * 获取模板详情（包含模型信息和分类名称）
     */
    public static function getDetailWithModel($id)
    {
        $info = self::alias('t')
            ->leftJoin('model_info m', 't.model_id = m.id')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->leftJoin('model_type mt', 'm.type_id = mt.id')
            ->field('t.*, m.model_name, m.model_code, m.input_schema, m.endpoint_url, m.task_type, p.provider_name, p.provider_code, mt.type_name')
            ->where('t.id', $id)
            ->find();
        
        if ($info) {
            // 解析多分类名称
            $info['category_names'] = self::resolveCategoryNames($info['category_ids'] ?? '');
            // 解析多分组名称
            $info['group_names'] = self::resolveGroupNames($info['group_ids'] ?? '');
        }
        
        return $info;
    }
    
    /**
     * 根据逗号分隔的分类ID字符串解析分类名称
     * @param string $categoryIds 逗号分隔的分类ID
     * @return string 逗号分隔的分类名称
     */
    public static function resolveCategoryNames($categoryIds)
    {
        if (empty($categoryIds)) {
            return '';
        }
        $ids = array_filter(array_map('intval', explode(',', $categoryIds)));
        if (empty($ids)) {
            return '';
        }
        $names = Db::name('generation_scene_category')
            ->where('id', 'in', $ids)
            ->column('name');
        return implode(',', $names);
    }
    
    /**
     * 根据逗号分隔的分组ID字符串解析分组名称
     * @param string $groupIds 逗号分隔的分组ID
     * @return string 逗号分隔的分组名称
     */
    public static function resolveGroupNames($groupIds)
    {
        if (empty($groupIds)) {
            return '';
        }
        $ids = array_filter(array_map('intval', explode(',', $groupIds)));
        if (empty($ids)) {
            return '';
        }
        $names = Db::name('generation_scene_group')
            ->where('id', 'in', $ids)
            ->column('name');
        return implode(',', $names);
    }
}
