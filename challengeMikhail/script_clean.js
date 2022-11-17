const fs = require('fs');

const start = Date.now();
const binMaps = {};
const binDict = [];

function getWordBin(word) {
    let n = 0;
    for (let char of word) {
        n |= 1<<char.charCodeAt();
    }
    return n;
}
function search(set, level) {
    const results = [];
    if (level === 1){
        for (let word of set) {
            results.push([word]);
        };
    } else {
        for (let i = 0; i < set.length - 1; i++){
            const newSet = [];
            const word1 = set[i];
            deepSet = set;
            let secondIndexStart = i + 1;
            for (let j = secondIndexStart; j < deepSet.length; j++){
                const word2 = deepSet[j];
                if (!(word2 & word1)) {
                    newSet.push(word2);
                }
            }
            if (newSet[level - 2]) {
                const deepResult = search(newSet, level - 1);
                for (let deepSet of deepResult) {
                    deepSet.push(word1);
                    results.push(deepSet);
                }
            }
        }
    }
    return results;
};

('' + fs.readFileSync('./words_alpha.txt'))
    .split('\r\n')
    .forEach(word => {
        if (word.length === 5 && !/(.).*\1/.test(word)) {
            const n = getWordBin(word);
            if (!binMaps[n]) {
                binMaps[n] = [];
                binDict.push(n);
            }
            binMaps[n].push(word);
        }
    });
const results = search(binDict, 5);
console.log(`Processing time: ${Date.now() - start}ms`);
console.log(`Sets count: ${results.length}`);
const resultsWithAnagrams = [];
results.forEach(set => {
    let setResults = [''];
    set.reverse().forEach(word => {
        const curStepResults = [...setResults];
        binMaps[word].forEach((w, index) => {
            const anagramResults = curStepResults.map(result => result = result ? `${result}, ${w}` : w);
            if (index === 0) {
                setResults = anagramResults;
            } else {
                setResults.push(...anagramResults);
            }  
        })
    })
    resultsWithAnagrams.push(...setResults);
});
console.log(`Total sets count with anagrams: ${resultsWithAnagrams.length}`);
fs.writeFileSync('results.txt', resultsWithAnagrams.join('\n'), 'utf-8');
