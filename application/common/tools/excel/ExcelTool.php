<?php
/**
 * Created by PhpStorm.
 * User: tyong
 * Date: 2020/4/19
 * Time: 15:46
 */

namespace app\common\tools\excel;


use app\common\exception\LogicException;
use think\Db;
use think\facade\Env;

class ExcelTool
{
    /**
     * 简单导出表格
     * $fileName:导出表格的文件名称
     * $cellName:表头定义,二维数组,
     *                      0:$data中的key,
     *                      1:$data中的key的名称,
     *                      2:当前列的宽度,不设置默认为30
     * 示例:
     *      [
     *          ['mobile','手机号','30'],
     *          ['name','姓名','30'],
     *      ]
     * $data:导出的数据:二维数组
     * 示例:
     *      [
     *          ["mobile"=>"13996854712","name"=>"小明"]
     *          ["mobile"=>"13996854713,"name"=>"小张"]
     *      ]
     */
    public static function export2Excel($fileName,$cellName,$data){
        include_once(Env::get("root_path")."extend/phpexcel/Classes/PHPExcel.php");
        $PHPExcel = new \PHPExcel();
        $xlsTitle = iconv('utf-8', 'gb2312//IGNORE', $fileName);
        $cellKey = array(
            'A','B','C','D','E','F','G','H','I','J','K','L','M',
            'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
            'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'
        );
        $PHPExcel->setActiveSheetIndex(0);
        //所有单元格（列）默认宽度
        $PHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(30);
        //处理表头
        foreach ($cellName as $k=>$v)
        {
            //设置表头数据
            $PHPExcel->getActiveSheet()->setCellValue($cellKey[$k]."1", $v[1]);
            //设置是否加粗
            $PHPExcel->getActiveSheet()->getStyle($cellKey[$k]."1")->getFont()->setBold(true);
            //垂直居中
            $PHPExcel->getActiveSheet()->getStyle($cellKey[$k]."1")->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            //设置列宽度
            if(!empty($v[2]) && $v[2] > 0)
            {
                $PHPExcel->getActiveSheet()->getColumnDimension($cellKey[$k])->setWidth($v[2]);
            }
        }
        //填充数据
        foreach ($data as $k=>$v)
        {
            foreach ($cellName as $k1=>$v1)
            {
                $PHPExcel->getActiveSheet()->setCellValue($cellKey[$k1].($k+2), $v[$v1[0]]);
                //格式统一左对齐,可选项:left,center,right
                $PHPExcel->getActiveSheet()->getStyle($cellKey[$k1].($k+2))->getAlignment()->setHorizontal("left");
            }
        }
        ob_end_clean();//清除缓冲区,避免乱码
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");
        $objWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel5');
        $objWriter->save('php://output');exit;
    }
    /**
     * 简单读取表格
     * $file_id:表格文件的id
     */
    public static function readFromExcel($file_id){
        include_once(Env::get("root_path")."extend/phpexcel/Classes/PHPExcel.php");
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        $uri=Db::table("system_file")->where(["id"=>$file_id])->value("uri");
        $xls_path=".".$uri;
        if (!$PHPReader->canRead($xls_path)) {
            $PHPReader = new \PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($xls_path)) {
                throw new LogicException("不可识别的表格文件");
            }
        }
        $PHPExcel = $PHPReader->load($xls_path);
        $res = $PHPExcel->getSheet(0)->toArray(null,true,true,true);
        return $res;
    }
}