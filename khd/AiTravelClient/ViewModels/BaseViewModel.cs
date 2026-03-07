using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Runtime.CompilerServices;
using System.Windows.Input;

namespace AiTravelClient.ViewModels
{
    /// <summary>
    /// ViewModel基类，提供属性变更通知、错误处理和忙碌状态管理
    /// </summary>
    public abstract class BaseViewModel : INotifyPropertyChanged
    {
        #region INotifyPropertyChanged Implementation

        public event PropertyChangedEventHandler PropertyChanged;

        /// <summary>
        /// 触发属性变更通知
        /// </summary>
        /// <param name="propertyName">属性名称</param>
        protected virtual void OnPropertyChanged([CallerMemberName] string propertyName = null)
        {
            PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propertyName));
        }

        /// <summary>
        /// 设置属性值并触发变更通知
        /// </summary>
        /// <typeparam name="T">属性类型</typeparam>
        /// <param name="field">字段引用</param>
        /// <param name="value">新值</param>
        /// <param name="propertyName">属性名称</param>
        /// <returns>如果值发生变化返回true</returns>
        protected bool SetProperty<T>(ref T field, T value, [CallerMemberName] string propertyName = null)
        {
            if (EqualityComparer<T>.Default.Equals(field, value))
                return false;

            field = value;
            OnPropertyChanged(propertyName);
            return true;
        }

        #endregion

        #region Busy State Management

        private bool _isBusy;
        /// <summary>
        /// 是否正在执行后台操作
        /// </summary>
        public bool IsBusy
        {
            get => _isBusy;
            set => SetProperty(ref _isBusy, value);
        }

        #endregion

        #region Error Handling

        private string _errorMessage;
        /// <summary>
        /// 最近一次错误消息
        /// </summary>
        public string ErrorMessage
        {
            get => _errorMessage;
            set
            {
                if (SetProperty(ref _errorMessage, value))
                {
                    OnPropertyChanged(nameof(HasError));
                }
            }
        }

        /// <summary>
        /// 是否存在错误
        /// </summary>
        public bool HasError => !string.IsNullOrEmpty(ErrorMessage);

        /// <summary>
        /// 清除错误消息
        /// </summary>
        protected void ClearError()
        {
            ErrorMessage = null;
        }

        /// <summary>
        /// 设置错误消息
        /// </summary>
        /// <param name="message">错误消息</param>
        protected void SetError(string message)
        {
            ErrorMessage = message;
        }

        /// <summary>
        /// 执行操作并捕获异常
        /// </summary>
        /// <param name="action">要执行的操作</param>
        /// <param name="errorMessage">错误消息前缀</param>
        protected void SafeExecute(Action action, string errorMessage = "操作失败")
        {
            try
            {
                ClearError();
                action();
            }
            catch (Exception ex)
            {
                SetError($"{errorMessage}: {ex.Message}");
            }
        }

        /// <summary>
        /// 异步执行操作并捕获异常
        /// </summary>
        /// <param name="action">要执行的异步操作</param>
        /// <param name="errorMessage">错误消息前缀</param>
        protected async System.Threading.Tasks.Task SafeExecuteAsync(Func<System.Threading.Tasks.Task> action, string errorMessage = "操作失败")
        {
            try
            {
                ClearError();
                IsBusy = true;
                await action();
            }
            catch (Exception ex)
            {
                SetError($"{errorMessage}: {ex.Message}");
            }
            finally
            {
                IsBusy = false;
            }
        }

        #endregion
    }

    /// <summary>
    /// 命令实现类，用于封装ICommand接口
    /// </summary>
    public class RelayCommand : ICommand
    {
        private readonly Action<object> _execute;
        private readonly Func<object, bool> _canExecute;

        public event EventHandler CanExecuteChanged
        {
            add { System.Windows.Input.CommandManager.RequerySuggested += value; }
            remove { System.Windows.Input.CommandManager.RequerySuggested -= value; }
        }

        /// <summary>
        /// 创建命令
        /// </summary>
        /// <param name="execute">执行方法</param>
        /// <param name="canExecute">可执行判断方法</param>
        public RelayCommand(Action<object> execute, Func<object, bool> canExecute = null)
        {
            _execute = execute ?? throw new ArgumentNullException(nameof(execute));
            _canExecute = canExecute;
        }

        /// <summary>
        /// 创建无参数命令
        /// </summary>
        /// <param name="execute">执行方法</param>
        /// <param name="canExecute">可执行判断方法</param>
        public RelayCommand(Action execute, Func<bool> canExecute = null)
            : this(
                execute != null ? new Action<object>(_ => execute()) : null,
                canExecute != null ? new Func<object, bool>(_ => canExecute()) : null)
        {
        }

        public bool CanExecute(object parameter)
        {
            return _canExecute == null || _canExecute(parameter);
        }

        public void Execute(object parameter)
        {
            _execute(parameter);
        }

        /// <summary>
        /// 触发CanExecuteChanged事件，强制刷新命令状态
        /// </summary>
        public void RaiseCanExecuteChanged()
        {
            System.Windows.Input.CommandManager.InvalidateRequerySuggested();
        }
    }

    /// <summary>
    /// 异步命令实现类
    /// </summary>
    public class AsyncRelayCommand : ICommand
    {
        private readonly Func<object, System.Threading.Tasks.Task> _execute;
        private readonly Func<object, bool> _canExecute;
        private bool _isExecuting;

        public event EventHandler CanExecuteChanged
        {
            add { System.Windows.Input.CommandManager.RequerySuggested += value; }
            remove { System.Windows.Input.CommandManager.RequerySuggested -= value; }
        }

        /// <summary>
        /// 创建异步命令
        /// </summary>
        /// <param name="execute">异步执行方法</param>
        /// <param name="canExecute">可执行判断方法</param>
        public AsyncRelayCommand(Func<object, System.Threading.Tasks.Task> execute, Func<object, bool> canExecute = null)
        {
            _execute = execute ?? throw new ArgumentNullException(nameof(execute));
            _canExecute = canExecute;
        }

        /// <summary>
        /// 创建无参数异步命令
        /// </summary>
        /// <param name="execute">异步执行方法</param>
        /// <param name="canExecute">可执行判断方法</param>
        public AsyncRelayCommand(Func<System.Threading.Tasks.Task> execute, Func<bool> canExecute = null)
            : this(
                execute != null ? new Func<object, System.Threading.Tasks.Task>(_ => execute()) : null,
                canExecute != null ? new Func<object, bool>(_ => canExecute()) : null)
        {
        }

        public bool CanExecute(object parameter)
        {
            return !_isExecuting && (_canExecute == null || _canExecute(parameter));
        }

        public async void Execute(object parameter)
        {
            if (!CanExecute(parameter))
                return;

            _isExecuting = true;
            RaiseCanExecuteChanged();

            try
            {
                await _execute(parameter);
            }
            finally
            {
                _isExecuting = false;
                RaiseCanExecuteChanged();
            }
        }

        /// <summary>
        /// 触发CanExecuteChanged事件
        /// </summary>
        public void RaiseCanExecuteChanged()
        {
            System.Windows.Input.CommandManager.InvalidateRequerySuggested();
        }
    }
}
