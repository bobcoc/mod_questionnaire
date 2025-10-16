-- 手动添加星级评分题型到数据库
-- 在Moodle已安装的情况下,需要手动执行此SQL
-- 注意: 星级评分基于Rate题型,使用response_rank表存储多行评分数据

-- 检查题型是否已存在
SELECT * FROM mdl_questionnaire_question_type WHERE typeid = 12;

-- 如果已存在但配置错误,先删除
-- DELETE FROM mdl_questionnaire_question_type WHERE typeid = 12;

-- 插入新题型(基于Rate题型)
INSERT INTO mdl_questionnaire_question_type (typeid, type, has_choices, response_table) 
VALUES (12, 'Star Rating', 'y', 'response_rank');

-- 验证插入是否成功
SELECT * FROM mdl_questionnaire_question_type ORDER BY typeid;
