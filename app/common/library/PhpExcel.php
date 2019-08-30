<?php
/**
 * Created by PhpStorm.
 * User: zhaoyindi
 * Date: 2018/5/18
 * Time: 18:50
 */
namespace app\common\library;

class PhpExcel
{
    static $letter = array('A','B','C','D','E','F','G','H', 'I','J','K','M', 'N');
    /**
     * @param $excelMame
     * @param $sheetRow
     * @param $data
     */
    public static function downloadExcel($excelMame, $sheetRow, $data) {
        $excel = new \PHPExcel();
        $PHPSheet = $excel->getActiveSheet(); //获得当前活动sheet的操作对象
        $PHPSheet->setTitle("sheet1"); //给当前活动sheet设置名称
        //Excel 头部
        foreach ($sheetRow as $key => $value){
            $PHPSheet->setCellValue(self::$letter[$key] .'1', $value);
        }
        $i = 2;
        //Excel内容
        foreach ($data as $key => $val) {
            $j = 0;
            foreach ($val as $k => $v) {
                $PHPSheet->setCellValue(self::$letter[$j++].$i, $val[$k]);
            }
            $i++;
            unset($data[$key]);//释放内存
        }

        $PHPWriter = \PHPExcel_IOFactory::createWriter($excel,'Excel2007');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=$excelMame.xlsx");
        header('Cache-Control: max-age=0');//禁止缓存
        $PHPWriter->save("php://output");
    }
}