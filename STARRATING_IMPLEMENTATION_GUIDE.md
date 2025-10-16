# Moodle Questionnaire 插件 - 添加星级评分题型实施指南

## 概述

本指南将帮助您在Moodle的questionnaire插件中添加一种新的星级评分题型,类似淘宝的星级打分系统。

## 实施步骤

### 步骤1: 定义新题型常量

**文件**: `mod/questionnaire/classes/question/question.php`

在文件的第32行附近(常量定义区域),添加新的题型常量:

```php
define('QUESSTARRATING', 12);  // 星级评分类型
```

在第117行附近,更新 `$qtypenames` 数组:

```php
private static $qtypenames = [
    QUESYESNO => 'yesno',
    QUESTEXT => 'text',
    QUESESSAY => 'essay',
    QUESRADIO => 'radio',
    QUESCHECK => 'check',
    QUESDROP => 'drop',
    QUESRATE => 'rate',
    QUESDATE => 'date',
    QUESNUMERIC => 'numerical',
    QUESPAGEBREAK => 'pagebreak',
    QUESSECTIONTEXT => 'sectiontext',
    QUESSLIDER => 'slider',
    QUESSTARRATING => 'starrating',  // 添加这一行
];
```

### 步骤2: 创建星级评分问题类

**文件**: `mod/questionnaire/classes/question/starrating.php`

此文件已创建,包含完整的星级评分题型逻辑。

### 步骤3: 更新语言文件

**文件**: `mod/questionnaire/lang/en/questionnaire.php`

在文件末尾添加以下语言字符串:

```php
$string['starrating'] = 'Star Rating';
$string['starrating_help'] = 'Display a star rating question where users can rate items using stars (like Taobao rating system).';
$string['starrating_link'] = 'mod/questionnaire/questions#Star_Rating';
$string['maxstars'] = 'Maximum number of stars';
$string['maxstars_help'] = 'Select the maximum number of stars to display (3-10 stars). Default is 5 stars.';
$string['starrating_hovertext'] = 'Click to rate {$a} star(s)';
$string['starrating_cleartext'] = 'Clear rating';
```

**文件**: `mod/questionnaire/lang/zh_cn/questionnaire.php` (如需中文支持)

```php
$string['starrating'] = '星级评分';
$string['starrating_help'] = '显示星级评分问题,用户可以使用星星对项目进行评分(类似淘宝评分系统)。';
$string['starrating_link'] = 'mod/questionnaire/questions#Star_Rating';
$string['maxstars'] = '最多星数';
$string['maxstars_help'] = '选择要显示的最大星数(3-10颗星)。默认为5颗星。';
$string['starrating_hovertext'] = '点击评 {$a} 星';
$string['starrating_cleartext'] = '清除评分';
```

### 步骤4: 更新locallib.php函数

**文件**: `mod/questionnaire/locallib.php`

在 `questionnaire_get_type()` 函数中(大约第478行),添加新的case:

```php
function questionnaire_get_type ($id) {
    switch ($id) {
        case 1:
            return get_string('yesno', 'questionnaire');
        case 2:
            return get_string('textbox', 'questionnaire');
        case 3:
            return get_string('essaybox', 'questionnaire');
        case 4:
            return get_string('radiobuttons', 'questionnaire');
        case 5:
            return get_string('checkboxes', 'questionnaire');
        case 6:
            return get_string('dropdown', 'questionnaire');
        case 8:
            return get_string('ratescale', 'questionnaire');
        case 9:
            return get_string('date', 'questionnaire');
        case 10:
            return get_string('numeric', 'questionnaire');
        case 11:
            return get_string('slider', 'questionnaire');
        case 12:  // 添加这一行
            return get_string('starrating', 'questionnaire');
        case 100:
            return get_string('sectiontext', 'questionnaire');
        case 99:
            return get_string('sectionbreak', 'questionnaire');
        default:
            return $id;
    }
}
```

### 步骤5: 创建Mustache模板

**文件**: `mod/questionnaire/templates/question_starrating.mustache`

创建此文件用于问题显示:

```mustache
{{!
    Star Rating question display template.
}}
<div class="qn-container star-rating-question" id="starrating_{{question.id}}">
    <fieldset class="star-rating-fieldset">
        <legend class="sr-only">{{qelements.caption}}</legend>
        
        {{#qelements.rows}}
        <div class="star-rating-row" data-question-id="{{question.id}}" data-choice-id="{{choiceid}}">
            <div class="star-rating-label">
                {{{content}}}
                {{#question.required}}
                <span class="required-label">*</span>
                {{/question.required}}
            </div>
            <div class="star-rating-stars">
                <input type="hidden" name="{{name}}" value="{{value}}" class="star-rating-value">
                {{#stars}}
                <span class="star {{#selected}}star-selected{{/selected}}" 
                      data-value="{{value}}" 
                      {{#disabled}}disabled{{/disabled}}
                      role="button" 
                      tabindex="0"
                      aria-label="{{value}} star(s)">
                    <i class="fa fa-star{{^selected}}-o{{/selected}}"></i>
                </span>
                {{/stars}}
                <span class="star-rating-text">(<span class="current-rating">{{value}}</span>/{{qelements.maxstars}})</span>
            </div>
        </div>
        {{/qelements.rows}}
    </fieldset>
</div>
```

**文件**: `mod/questionnaire/templates/response_starrating.mustache`

创建此文件用于响应显示:

```mustache
{{!
    Star Rating response display template.
}}
<div class="response-star-rating">
    {{#rows}}
    <div class="star-rating-response-row">
        <div class="star-rating-response-label">
            {{{content}}}
        </div>
        <div class="star-rating-response-stars">
            {{#stars}}
            <i class="fa fa-star{{^filled}}-o{{/filled}} {{#filled}}star-filled{{/filled}}"></i>
            {{/stars}}
            <span class="star-rating-response-value">({{value}}/{{maxstars}})</span>
        </div>
    </div>
    {{/rows}}
</div>
```

### 步骤6: 创建CSS样式文件

**文件**: `mod/questionnaire/styles_starrating.css`

```css
/* Star Rating Question Styles */
.star-rating-question {
    margin: 20px 0;
}

.star-rating-fieldset {
    border: none;
    padding: 0;
    margin: 0;
}

.star-rating-row {
    margin: 15px 0;
    padding: 10px;
    border-bottom: 1px solid #e0e0e0;
}

.star-rating-row:last-child {
    border-bottom: none;
}

.star-rating-label {
    font-weight: 500;
    margin-bottom: 8px;
    color: #333;
}

.required-label {
    color: #d9534f;
    margin-left: 3px;
}

.star-rating-stars {
    display: flex;
    align-items: center;
    gap: 5px;
}

.star {
    cursor: pointer;
    font-size: 24px;
    color: #ddd;
    transition: all 0.2s ease;
    display: inline-block;
}

.star:hover,
.star.star-hover {
    color: #ffa500;
    transform: scale(1.1);
}

.star.star-selected {
    color: #ff9500;
}

.star[disabled] {
    cursor: not-allowed;
    opacity: 0.5;
}

.star-rating-text {
    margin-left: 10px;
    font-size: 14px;
    color: #666;
}

.current-rating {
    font-weight: bold;
    color: #333;
}

/* Response Display Styles */
.response-star-rating {
    padding: 10px 0;
}

.star-rating-response-row {
    margin: 15px 0;
    padding: 10px;
    background-color: #f9f9f9;
    border-radius: 4px;
}

.star-rating-response-label {
    font-weight: 500;
    margin-bottom: 8px;
    color: #333;
}

.star-rating-response-stars {
    display: flex;
    align-items: center;
    gap: 3px;
}

.star-rating-response-stars i {
    font-size: 20px;
    color: #ddd;
}

.star-rating-response-stars i.star-filled {
    color: #ff9500;
}

.star-rating-response-value {
    margin-left: 10px;
    font-size: 14px;
    color: #666;
}

/* Responsive Design */
@media (max-width: 768px) {
    .star {
        font-size: 20px;
    }
    
    .star-rating-response-stars i {
        font-size: 18px;
    }
}
```

### 步骤7: 创建JavaScript文件

**文件**: `mod/questionnaire/javascript/starrating.js`

```javascript
/**
 * Star Rating question type JavaScript
 */

(function() {
    'use strict';

    /**
     * Initialize star rating functionality
     */
    function initStarRating() {
        var starContainers = document.querySelectorAll('.star-rating-row');
        
        starContainers.forEach(function(container) {
            var stars = container.querySelectorAll('.star:not([disabled])');
            var input = container.querySelector('.star-rating-value');
            var ratingText = container.querySelector('.current-rating');
            
            if (!stars.length || !input) {
                return;
            }
            
            // Click handler
            stars.forEach(function(star) {
                star.addEventListener('click', function() {
                    var value = parseInt(this.getAttribute('data-value'));
                    updateRating(stars, input, ratingText, value);
                });
                
                // Keyboard support
                star.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        var value = parseInt(this.getAttribute('data-value'));
                        updateRating(stars, input, ratingText, value);
                    }
                });
            });
            
            // Hover effect
            stars.forEach(function(star, index) {
                star.addEventListener('mouseenter', function() {
                    highlightStars(stars, index + 1);
                });
            });
            
            container.addEventListener('mouseleave', function() {
                var currentValue = parseInt(input.value) || 0;
                highlightStars(stars, currentValue);
            });
        });
    }
    
    /**
     * Update rating value
     */
    function updateRating(stars, input, ratingText, value) {
        input.value = value;
        if (ratingText) {
            ratingText.textContent = value;
        }
        highlightStars(stars, value);
    }
    
    /**
     * Highlight stars up to the given value
     */
    function highlightStars(stars, value) {
        stars.forEach(function(star, index) {
            var starValue = index + 1;
            var icon = star.querySelector('i');
            
            if (starValue <= value) {
                star.classList.add('star-selected');
                star.classList.remove('star-hover');
                if (icon) {
                    icon.className = 'fa fa-star';
                }
            } else {
                star.classList.remove('star-selected');
                if (icon) {
                    icon.className = 'fa fa-star-o';
                }
            }
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStarRating);
    } else {
        initStarRating();
    }
    
    // Re-initialize for dynamic content
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    initStarRating();
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
})();
```

### 步骤8: 更新数据库

需要在数据库的 `questionnaire_question_type` 表中添加新记录。

创建数据库升级文件或手动执行SQL:

```sql
INSERT INTO {questionnaire_question_type} (typeid, type, has_choices, response_table) 
VALUES (12, 'Star Rating', 'y', 'response_rank');
```

或者在 `mod/questionnaire/db/install.php` 中添加:

```php
// 在install函数中添加
$DB->insert_record('questionnaire_question_type', [
    'typeid' => 12,
    'type' => 'Star Rating',
    'has_choices' => 'y',
    'response_table' => 'response_rank'
]);
```

### 步骤9: 更新behat测试配置

**文件**: `mod/questionnaire/tests/behat/behat_mod_questionnaire.php`

在 `$validtypes` 数组中添加新类型(大约第109行):

```php
$validtypes = array(
    '----- Page Break -----',
    'Check Boxes',
    'Date',
    'Dropdown Box',
    'Essay Box',
    'Label',
    'Numeric',
    'Radio Buttons',
    'Rate (scale 1..5)',
    'Text Box',
    'Yes/No',
    'Slider',
    'Star Rating');  // 添加这一行
```

### 步骤10: 清除缓存

修改完成后,需要清除Moodle缓存:

```bash
# 通过管理界面
Site administration > Development > Purge all caches

# 或通过命令行
php admin/cli/purge_caches.php
```

## 使用方法

1. 进入问卷管理界面
2. 点击"添加问题"
3. 选择"Star Rating"题型
4. 设置问题内容和选项:
   - **Question Name**: 问题名称
   - **Question Text**: 问题描述
   - **Maximum stars**: 最大星数(3-10)
   - **Possible answers**: 每行一个评分项
5. 保存问题

## 示例配置

**Question Text**: 请对以下服务进行评分

**Possible answers**:
```
商品质量
物流速度
客服态度
整体满意度
```

**Maximum stars**: 5

## 文件清单

需要创建/修改的文件:

1. ✅ `mod/questionnaire/classes/question/starrating.php` - 新建
2. ⏳ `mod/questionnaire/classes/question/question.php` - 修改
3. ⏳ `mod/questionnaire/locallib.php` - 修改
4. ⏳ `mod/questionnaire/lang/en/questionnaire.php` - 修改
5. ⏳ `mod/questionnaire/templates/question_starrating.mustache` - 新建
6. ⏳ `mod/questionnaire/templates/response_starrating.mustache` - 新建
7. ⏳ `mod/questionnaire/styles_starrating.css` - 新建
8. ⏳ `mod/questionnaire/javascript/starrating.js` - 新建
9. ⏳ `mod/questionnaire/db/install.php` - 修改
10. ⏳ `mod/questionnaire/tests/behat/behat_mod_questionnaire.php` - 修改

## 注意事项

1. 备份数据库和代码再进行修改
2. 在测试环境先验证功能
3. 确保Font Awesome图标库已加载(Moodle默认包含)
4. 考虑添加单元测试
5. 可能需要升级插件版本号

## 下一步优化

1. 添加半星评分支持
2. 自定义星星颜色
3. 添加评分说明文字(如"1星=很差,5星=很好")
4. 支持移动端触摸操作优化
5. 添加数据分析和报表功能

## 技术支持

如遇到问题,请检查:
- Moodle调试模式输出
- 浏览器控制台错误
- 数据库日志
- PHP错误日志
