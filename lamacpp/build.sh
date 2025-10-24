#!/bin/bash

# Exit on any error
set -e

SOURCE_DIR="$HOME/lamacpp/llama.cpp"
TAG=$(git -C "$SOURCE_DIR" describe --tags)
BUILD_DIR="$SOURCE_DIR/build-$TAG"

# Crucial: Set environment variables to force NVCC to use GCC 14
export CC=/usr/bin/gcc-14
export CXX=/usr/bin/g++-14
export CUDAHOSTCXX=/usr/bin/g++-14  # Specifically tells NVCC which host compiler to use
export NVCC_CCBIN=/usr/bin/g++-14   # Another variable NVCC checks for the host compiler

# Standard CUDA paths
export CUDA_HOME=/usr/local/cuda
export PATH="/usr/local/cuda/bin:$PATH"
export LD_LIBRARY_PATH="/usr/local/cuda/lib64:$LD_LIBRARY_PATH"

echo "Using build directory: $BUILD_DIR"
echo "Using C compiler: $(which $CC)"
echo "Using C++ compiler: $(which $CXX)"
echo "Using CUDA host compiler: $CUDAHOSTCXX"

# Clean previous build
rm -rf "$BUILD_DIR"

# Run cmake and build
cmake -B "$BUILD_DIR" -S "$SOURCE_DIR" \
  -DGGML_CUDA=ON \
  -DCMAKE_CUDA_FLAGS="-allow-unsupported-compiler" \  # Keep this as a safety net
  -DGGML_CUDA_FA_ALL_QUANTS=ON \
  -DGGML_CUDA_FORCE_MMQ=ON \
  -DLLAMA_CURL=OFF \
  -DCMAKE_BUILD_TYPE=Release

cmake --build "$BUILD_DIR" --config Release -j $(nproc)
