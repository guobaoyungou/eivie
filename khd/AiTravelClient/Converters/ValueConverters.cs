using System;
using System.Globalization;
using System.Windows;
using System.Windows.Data;
using System.Windows.Media;
using AiTravelClient.Services;
using AiTravelClient.ViewModels;

namespace AiTravelClient.Converters
{
    /// <summary>
    /// Bool转颜色转换器
    /// </summary>
    public class BoolToColorConverter : IValueConverter
    {
        public Brush TrueColor { get; set; } = new SolidColorBrush(Color.FromRgb(76, 175, 80)); // Green
        public Brush FalseColor { get; set; } = new SolidColorBrush(Color.FromRgb(158, 158, 158)); // Gray

        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (value is bool boolValue)
            {
                return boolValue ? TrueColor : FalseColor;
            }
            return FalseColor;
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            throw new NotImplementedException();
        }
    }

    /// <summary>
    /// 网络状态转颜色转换器
    /// </summary>
    public class NetworkStatusToColorConverter : IValueConverter
    {
        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (value is NetworkStatus status)
            {
                return status switch
                {
                    NetworkStatus.Connected => new SolidColorBrush(Color.FromRgb(76, 175, 80)), // Green
                    NetworkStatus.Error => new SolidColorBrush(Color.FromRgb(244, 67, 54)), // Red
                    NetworkStatus.Disconnected => new SolidColorBrush(Color.FromRgb(158, 158, 158)), // Gray
                    _ => new SolidColorBrush(Color.FromRgb(158, 158, 158))
                };
            }
            return new SolidColorBrush(Color.FromRgb(158, 158, 158));
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            throw new NotImplementedException();
        }
    }

    /// <summary>
    /// 日志级别转颜色转换器
    /// </summary>
    public class LogLevelToColorConverter : IValueConverter
    {
        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (value is LogLevel level)
            {
                return level switch
                {
                    LogLevel.DEBUG => new SolidColorBrush(Color.FromRgb(128, 128, 128)), // Gray
                    LogLevel.INFO => new SolidColorBrush(Color.FromRgb(33, 150, 243)), // Blue
                    LogLevel.WARN => new SolidColorBrush(Color.FromRgb(255, 152, 0)), // Orange
                    LogLevel.ERROR => new SolidColorBrush(Color.FromRgb(244, 67, 54)), // Red
                    _ => new SolidColorBrush(Color.FromRgb(0, 0, 0))
                };
            }
            return new SolidColorBrush(Color.FromRgb(0, 0, 0));
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            throw new NotImplementedException();
        }
    }

    /// <summary>
    /// Bool转可见性转换器
    /// </summary>
    public class BoolToVisibilityConverter : IValueConverter
    {
        public bool Inverse { get; set; } = false;

        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (value is bool boolValue)
            {
                bool result = Inverse ? !boolValue : boolValue;
                return result ? Visibility.Visible : Visibility.Collapsed;
            }
            return Visibility.Collapsed;
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (value is Visibility visibility)
            {
                bool result = visibility == Visibility.Visible;
                return Inverse ? !result : result;
            }
            return false;
        }
    }

    /// <summary>
    /// Bool取反转换器
    /// </summary>
    public class InverseBoolConverter : IValueConverter
    {
        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (value is bool boolValue)
            {
                return !boolValue;
            }
            return true;
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (value is bool boolValue)
            {
                return !boolValue;
            }
            return false;
        }
    }

    /// <summary>
    /// 文件数量转文本转换器
    /// </summary>
    public class FileCountToTextConverter : IValueConverter
    {
        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (value is int count)
            {
                return count == 0 ? "无文件" : $"{count}个文件";
            }
            return "0个文件";
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            throw new NotImplementedException();
        }
    }

    /// <summary>
    /// 字节数转大小转换器
    /// </summary>
    public class BytesToSizeConverter : IValueConverter
    {
        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (value is long bytes)
            {
                if (bytes < 1024)
                    return $"{bytes} B";
                else if (bytes < 1024 * 1024)
                    return $"{bytes / 1024.0:F2} KB";
                else if (bytes < 1024 * 1024 * 1024)
                    return $"{bytes / (1024.0 * 1024):F2} MB";
                else
                    return $"{bytes / (1024.0 * 1024 * 1024):F2} GB";
            }
            return "0 B";
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            throw new NotImplementedException();
        }
    }

    /// <summary>
    /// 秒数转时间跨度转换器
    /// </summary>
    public class SecondsToTimeSpanConverter : IValueConverter
    {
        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (value is int seconds)
            {
                int hours = seconds / 3600;
                int minutes = (seconds % 3600) / 60;
                
                if (hours > 0)
                    return $"{hours}小时{minutes}分钟";
                else if (minutes > 0)
                    return $"{minutes}分钟";
                else
                    return $"{seconds}秒";
            }
            return "0秒";
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            throw new NotImplementedException();
        }
    }

    /// <summary>
    /// 字符串转颜色画刷转换器
    /// </summary>
    public class StringToColorBrushConverter : IValueConverter
    {
        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (value is string colorString && !string.IsNullOrWhiteSpace(colorString))
            {
                try
                {
                    return new SolidColorBrush((Color)ColorConverter.ConvertFromString(colorString));
                }
                catch
                {
                    return new SolidColorBrush(Colors.Black);
                }
            }
            return new SolidColorBrush(Colors.Black);
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            throw new NotImplementedException();
        }
    }

    /// <summary>
    /// Null或空字符串转可见性转换器
    /// </summary>
    public class NullOrEmptyToVisibilityConverter : IValueConverter
    {
        public bool Inverse { get; set; } = false;

        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            bool isEmpty = value == null || (value is string str && string.IsNullOrWhiteSpace(str));
            bool result = Inverse ? isEmpty : !isEmpty;
            return result ? Visibility.Visible : Visibility.Collapsed;
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            throw new NotImplementedException();
        }
    }

    /// <summary>
    /// 数字大于0转可见性转换器
    /// </summary>
    public class NumberGreaterThanZeroToVisibilityConverter : IValueConverter
    {
        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (value is int intValue)
            {
                return intValue > 0 ? Visibility.Visible : Visibility.Collapsed;
            }
            if (value is double doubleValue)
            {
                return doubleValue > 0 ? Visibility.Visible : Visibility.Collapsed;
            }
            return Visibility.Collapsed;
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            throw new NotImplementedException();
        }
    }

    /// <summary>
    /// 百分比格式化转换器
    /// </summary>
    public class PercentageConverter : IValueConverter
    {
        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (value is double doubleValue)
            {
                return $"{doubleValue:F1}%";
            }
            return "0%";
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            throw new NotImplementedException();
        }
    }

    /// <summary>
    /// 日志级别转文本转换器
    /// </summary>
    public class LogLevelToTextConverter : IValueConverter
    {
        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (value == null)
                return "全部";

            if (value is LogLevel level)
            {
                return level switch
                {
                    LogLevel.DEBUG => "调试",
                    LogLevel.INFO => "信息",
                    LogLevel.WARN => "警告",
                    LogLevel.ERROR => "错误",
                    _ => "未知"
                };
            }
            return "全部";
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            throw new NotImplementedException();
        }
    }
}
