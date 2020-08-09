<?php
/**
 * Created by PhpStorm.
 * User: chenyu
 * Date: 2020-08-04
 * Time: 21:28
 */

namespace JoseChan\Admin\Extensions\MultipleDatetime;


use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Form\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Laravel-admin Extension for Multiple DateTime Select
 * Class MultiDateTimeSelect
 * @package App\Admin\Extensions\Form\Field
 */
class MultipleDatetime extends Field
{

    public function __construct($column = '', array $arguments = [])
    {
        $my_column = $column;
        if (Str::contains($my_column, '->') || Str::contains($my_column, '.')) {
            $my_column = str_replace('->', '.', $my_column);
            list($my_column, $other_key) = explode(".", $my_column);
            $this->otherKey = $other_key;
        }
        $this->relationKey = $my_column;

        parent::__construct($column, $arguments);
    }

    protected static $css = [
        "vendor/jose-chan/multiple-datetime/resources/assets/layui/layui.css"
    ];

    protected static $js = [
        "vendor/jose-chan/multiple-datetime/resources/assets/layui/layui.js",
        "vendor/jose-chan/multiple-datetime/resources/assets/xm-select/xm-select.js"
    ];

    protected $view = "multiple-datetime::multiple-datetime";

    protected $relationKey;
    protected $otherKey;

    /**
     * get Default Value When Edit
     * @return false|string
     */
    private function getDefaultValue()
    {
        $value = [];
        if (!empty($this->value)) {
            foreach ($this->value as $datetime) {
                $value[] = [
                    "name" => $datetime,
                    "value" => $datetime,
                    "selected" => true
                ];
            }
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function getFormName()
    {
        return !empty($this->otherKey) ? "{$this->relationKey}[{$this->otherKey}]" : $this->relationKey;
    }

    public function getIdName()
    {
        return !empty($this->otherKey) ? $this->otherKey : $this->relationKey;
    }

    public function relateField($field)
    {
        $this->otherKey = $field;
    }

    /**
     * javascript
     * @return string
     */
    private function script()
    {
        $value = $this->getDefaultValue();
        $form_name = $this->getFormName();
        $id_name = $this->getIdName();
        $script = <<<EOT
let {$id_name}val = {$value};
var xm_select = xmSelect.render({
	el: '#multi-date-selector-{$id_name}', 
	name: '{$form_name}',
	data:{$id_name}val,
	content: '<div id="laydate-{$id_name}" />',
	height: 'auto',
	autoRow: true,
	on: function(data){
		if(!data.isAdd){
			dateSelect(xm_select.getValue('value'));
		}
	}
})

layui.laydate.render({
	elem: '#laydate-{$id_name}',
	type: 'datetime',
	position: 'static',
	showBottom: true,
	format: 'yyyy-MM-dd HH:mm:ss',
	change: function(){
		dateSelect(xm_select.getValue('value'));
	},
	done: function(value){
		var values = xm_select.getValue('value');
		var index = values.findIndex(function(val){
			return val === value
		});
		
		if(index != -1){
			values.splice(index, 1);
		}else{
			values.push(value);
		}

		dateSelect(values);
		
		xm_select.update({
			data: values.map(function(val){
				return {
					name: val,
					value: val,
					selected: true,
				}
			})
		})
	},
	ready: removeAll,
})

function removeAll(){
	document.querySelectorAll('#laydate-{$id_name} td[lay-ymd].layui-this').forEach(function(dom){
		dom.classList.remove('layui-this');
	});
}

function dateSelect(values){
	removeAll();
	values.forEach(function(val){
		var dom = document.querySelector('#laydate-{$id_name} td[lay-ymd="'+val.replace(/-0([1-9])/g, '-$1')+'"]');
		dom && dom.classList.add('layui-this');
	});
}

EOT;

        return $script;
    }

    /**
     * 渲染
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    public function render()
    {
        $this->variables = array_merge($this->variables, ["id_name" => $this->getIdName()]);
        Admin::script($this->script());
        return parent::render(); // TODO: Change the autogenerated stub
    }

    /**
     * 填充回显数据
     * @param array $data
     */
    public function fill($data)
    {
        if ($this->form && $this->form->shouldSnakeAttributes()) {
            $key = Str::snake($this->column);
        } else {
            $key = $this->column;
        }

        if (Str::contains($key, '.')) {
            list($relation_key) = explode('.', $key);
            $relations = Arr::get($data, $relation_key);
        } else {
            $relations = Arr::get($data, $key);
        }

        if (is_string($relations)) {
            $this->value = explode(',', $relations);
        }

        if (!is_array($relations)) {
            return;
        }

        $first = current($relations);

        if (is_null($first)) {
            $this->value = null;

            return;

        } elseif (is_array($first) && !empty($this->otherKey)) {
            foreach ($relations as $relation) {
                $this->value[] = Arr::get($relation, $this->otherKey);
            }

            // MultipleSelect value store as a column.
        } elseif (is_array($relations) && !empty($this->otherKey)) {
            $this->value = explode(",", Arr::get($relations, $this->otherKey));
        } else {
            $this->value[] = $relations;
        }
    }

    /**
     * 返回入库数据结构
     * @param $value
     * @return array|mixed
     */
    public function prepare($value)
    {
        if (method_exists($this->form->model(), $this->relationKey)
        ) {
            if ($this->form->model()->{$this->relationKey}() instanceof HasMany) {
                if (isset($this->otherKey) && isset($value[$this->otherKey])) {
                    $values = explode(",", $value[$this->otherKey]);
                    $value = [];

                    //获取已有的关系
                    $collection = $this->form->model()->{$this->relationKey};
                    /** @var \Iterator $related */
                    $related = $collection->getIterator();
                    // 自动复用记录
                    foreach ($values as $item) {
                        /** @var Model $current */
                        if ($current = $related->current()) {
                            //更新
                            $value[] = [
                                $current->getKeyName() => $current->getKey(),
                                $this->otherKey => $item,
                                Form::REMOVE_FLAG_NAME => 0,
                            ];
                            $related->next();
                        } else {
                            //新增
                            $value[] = [
                                $this->otherKey => $item,
                                Form::REMOVE_FLAG_NAME => 0,
                            ];
                        }
                    }
                    // 清理多余的记录
                    while ($current = $related->current()) {
                        //删除
                        $value[] = [
                            $current->getKeyName() => $current->getKey(),
                            Form::REMOVE_FLAG_NAME => 1,
                        ];
                        $related->next();
                    }
                }
            }
        }
        return $value;
    }
}