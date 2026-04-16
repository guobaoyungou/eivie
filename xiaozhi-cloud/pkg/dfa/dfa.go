package dfa

import (
	"fmt"
)

// DFAState DFA 状态节点
type DFAState struct {
	IsEnd   bool              // 是否为终止状态（匹配到敏感词）
	Fail    *DFAState         // 失败指针
	Next    map[rune]*DFAState // 子节点映射
	Depth   int               // 当前深度
	Output  []string          // 输出（该节点匹配到的所有敏感词）
}

// DFAEngine DFA 敏感词匹配引擎
type DFAEngine struct {
	root     *DFAState
	built    bool
	size     int
}

// NewDFAEngine 创建空的 DFA 引擎
func NewDFAEngine() *DFAEngine {
	return &DFAEngine{
		root: &DFAState{
			Next: make(map[rune]*DFAState),
		},
	}
}

// BuildDFA 从敏感词列表构建 AC 自动机（DFA + 失败指针优化）
func BuildDFA(words []string) *DFAEngine {
	engine := NewDFAEngine()

	// Step 1: 构建 Trie 树
	for _, word := range words {
		engine.AddWord(word)
	}

	// Step 2: 构建失败指针 (BFS)
	engine.buildFailPointers()

	engine.built = true
	engine.size = len(words)
	return engine
}

// AddWord 向 Trie 中添加一个敏感词
func (e *DFAEngine) AddWord(word string) {
	node := e.root

	for _, ch := range []rune(word) {
		if next, ok := node.Next[ch]; ok {
			node = next
		} else {
			newNode := &DFAState{
				Next:   make(map[rune]*DFAState),
				Depth:  node.Depth + 1,
			}
			node.Next[ch] = newNode
			node = newNode
		}
	}

	node.IsEnd = true
	node.Output = append(node.Output, word)
	e.size++
}

// RemoveWord 移除一个敏感词（需要重建失败指针）
func (e *DFAEngine) RemoveWord(word string) bool {
	// 在 Trie 中找到并移除
	nodes := []*DFAState{e.root}
	found := true
	
	for _, ch := range []rune(word) {
		next, ok := nodes[len(nodes)-1].Next[ch]
		if !ok {
			found = false
			break
		}
		nodes = append(nodes, next)
	}

	if found && len(nodes) > 1 {
		last := nodes[len(nodes)-1]
		last.IsEnd = false
		last.Output = nil
		e.size--
		
		// 重建失败指针
		e.buildFailPointers()
		return true
	}
	return false
}

// buildFailPointers 构建 AC 自动机的失败指针
func (e *DFAEngine) buildFailPointers() {
	queue := make([]*DFAState, 0)

	// 第一层节点的失败指针指向根
	for _, child := range e.root.Next {
		child.Fail = e.root
		queue = append(queue, child)
	}

	// BFS 构建
	for len(queue) > 0 {
		current := queue[0]
		queue = queue[1:]

		for char, child := range current.Next {
			queue = append(queue, child)

			// 找到父节点失败指针的第一个匹配子节点
			fail := current.Fail
			for fail != nil {
				if next, ok := fail.Next[char]; ok {
					child.Fail = next
					break
				}
				fail = fail.Fail
			}
			
			if child.Fail == nil {
				child.Fail = e.root
			}

			// 合并输出
			if child.Fail.IsEnd {
				child.Output = append(child.Output, child.Fail.Output...)
				child.IsEnd = child.IsEnd || child.Fail.IsEnd
			}
		}
	}
}

// Match 检查文本是否包含敏感词（返回是否命中 + 命中的词）
// 时间复杂度 O(n + m)，n 为文本长度，m 为命中次数
func (e *DFAEngine) Match(text string) (bool, string) {
	node := e.root
	runes := []rune(text)
	var lastMatched string

	for i, ch := range runes {
		// 回溯查找匹配
		for node != nil {
			if next, ok := node.Next[ch]; ok {
				node = next
				break
			}
			node = node.Fail
		}

		if node == nil {
			node = e.root
			continue
		}

		// 检查是否到达终止状态
		if node.IsEnd && len(node.Output) > 0 {
			lastMatched = node.Output[0]
			return true, lastMatched
		}

		_ = i // 避免未使用变量警告
	}

	return false, ""
}

// MatchAll 匹配文本中所有敏感词
func (e *DFAEngine) MatchAll(text string) []string {
	var results []string
	node := e.root
	runes := []rune(text)

	for _, ch := range runes {
		for node != nil {
			if next, ok := node.Next[ch]; ok {
				node = next
				break
			}
			node = node.Fail
		}

		if node == nil {
			node = e.root
			continue
		}

		if node.IsEnd && len(node.Output) > 0 {
			results = append(results, node.Output...)
		}
	}

	return results
}

// Replace 替换文本中的敏感词为掩码字符
func (e *DFAEngine) Replace(text string, mask rune) string {
	node := e.root
	runes := []rune(text)
	result := make([]rune, len(runes))
	copy(result, runes)

	i := 0
	for i < len(runes) {
		ch := runes[i]

		for node != nil {
			if next, ok := node.Next[ch]; ok {
				node = next
				break
			}
			node = node.Fail
		}

		if node == nil {
			node = e.root
			i++
			continue
		}

		if node.IsEnd && len(node.Output) > 0 {
			// 找到最长匹配
			maxLen := 0
			for _, word := range node.Output {
				l := len([]rune(word))
				if l > maxLen {
					maxLen = l
				}
			}

			for j := i; j <= i+maxLen-1 && j < len(result); j++ {
				result[j] = mask
			}
			node = e.root
			i += maxLen
		} else {
			i++
		}
	}

	return string(result)
}

// Size 返回已添加的敏感词数量
func (e *DFAEngine) Size() int { return e.size }

// String 返回引擎信息字符串
func (e *DFAEngine) String() string {
	return fmt.Sprintf("DFAEngine{size=%d, built=%v}", e.size, e.built)
}
