package pipeline

import (
	"bufio"
	"os"
	"strings"

	"github.com/ai-eivie/xiaozhi-cloud/pkg/dfa"
	"go.uber.org/zap"
)

// Filter DFA敏感词过滤器
type Filter struct {
	engine *dfa.DFAEngine
	filePath string
	mu      sync.RWMutex
}

// NewFilter 从文件创建 DFA 过滤器
func NewFilter(filePath string) (*Filter, error) {
	f := &Filter{filePath: filePath}
	
	if err := f.Load(filePath); err != nil {
		return nil, err
	}
	
	return f, nil
}

// Load 加载敏感词文件并构建 DFA 引擎
func (f *Filter) Load(filePath string) error {
	file, err := os.Open(filePath)
	if err != nil {
		// 如果文件不存在，使用空引擎
		logger.Warn("敏感词文件不存在，使用空过滤器", zap.String("path", filePath), zap.Error(err))
		f.engine = dfa.NewDFAEngine()
		return nil
	}
	defer file.Close()

	var words []string
	scanner := bufio.NewScanner(file)
	for scanner.Scan() {
		line := strings.TrimSpace(scanner.Text())
		if line != "" && !strings.HasPrefix(line, "#") {
			words = append(words, line)
		}
	}

	if err := scanner.Err(); err != nil {
		return fmt.Errorf("读取敏感词文件错误: %w", err)
	}

	f.mu.Lock()
	f.engine = dfa.BuildDFA(words)
	f.mu.Unlock()

	logger.Info("敏感词过滤器加载完成",
		zap.Int("word_count", len(words)),
		zap.String("path", filePath),
	)
	return nil
}

// Match 检查文本是否包含敏感词（线程安全）
func (f *Filter) Match(text string) bool {
	f.mu.RLock()
	defer f.mu.RUnlock()
	
	if f.engine == nil {
		return false
	}
	
	hit, _ := f.engine.Match(text)
	return hit
}

// MatchWithWord 匹配并返回命中的敏感词
func (f *Filter) MatchWithWord(text string) (bool, string) {
	f.mu.RLock()
	defer f.mu.RUnlock()

	if f.engine == nil {
		return false, ""
	}

	return f.engine.Match(text)
}

// Reload 重新加载敏感词库（支持热更新）
func (f *Filter) Reload(filePath string) error {
	if filePath == "" {
		filePath = f.filePath
	}
	return f.Load(filePath)
}

// AddWord 动态添加敏感词
func (f *Filter) AddWord(word string) {
	f.mu.Lock()
	defer f.mu.Unlock()
	
	if f.engine == nil {
		f.engine = dfa.NewDFAEngine()
	}
	
	f.engine.AddWord(word)
}

// RemoveWord 动态移除敏感词（需要重建）
func (f *Filter) RemoveWord(word string) {
	f.mu.Lock()
	defer f.mu.Unlock()
	
	if f.engine != nil {
		f.engine.RemoveWord(word)
	}
}
